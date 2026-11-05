<?php

declare(strict_types=1);

namespace Neutrino\Repositories;

use Generator;
use InvalidArgumentException;
use Neutrino\Constants\Services;
use Neutrino\Interfaces\Repositories\RepositoryInterface;
use Neutrino\Repositories\Exceptions\TransactionException;
use Phalcon\Di\Injectable;
use Phalcon\Messages\MessageInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;
use Phalcon\Mvc\ModelInterface;
use RuntimeException;
use Throwable;

/**
 * Queries and writes of a model: `protected ?string $modelClass = User::class;`. See {@see RepositoryInterface}
 * for the conditions.
 */
abstract class Repository extends Injectable implements RepositoryInterface
{
    /**
     * Operators accepted in the conditions.
     *
     * @var list<string>
     */
    public const array OPERATORS = ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL'];

    /**
     * @var class-string<ModelInterface<mixed>>|null
     */
    protected ?string $modelClass = null;

    /**
     * @var list<MessageInterface|string>
     */
    protected array $messages = [];

    /**
     * Attributes of the model, by name.
     *
     * @var array<string, true>|null
     */
    private ?array $attributes = null;

    /**
     * @param class-string<ModelInterface<mixed>>|null $modelClass
     */
    public function __construct(?string $modelClass = null)
    {
        $this->modelClass = $modelClass ?? $this->modelClass;

        if ($this->modelClass === null) {
            throw new RuntimeException(static::class . ' must have a $modelClass.');
        }
    }

    public function all(): ResultsetInterface
    {
        return self::resultset($this->model()::find());
    }

    public function count(?array $params = null): int
    {
        $count = $this->model()::count($this->paramsToCriteria($params ?? []));

        // A ResultsetInterface when the count is grouped.
        return is_int($count) ? $count : ($count instanceof \Countable ? count($count) : 0);
    }

    public function find(array $params = [], ?array $order = null, ?int $limit = null, ?int $offset = null): ResultsetInterface
    {
        return self::resultset($this->model()::find($this->paramsToCriteria($params, $order, $limit, $offset)));
    }

    public function first(array $params = [], ?array $order = null): ?ModelInterface
    {
        $model = $this->model()::findFirst($this->paramsToCriteria($params, $order));

        return $model instanceof ModelInterface ? $model : null;
    }

    /**
     * @param array<string, mixed>           $params
     * @param array<int|string, string>|null $order
     */
    public function average(string $column, array $params = [], ?array $order = null, ?int $limit = null, ?int $offset = null): mixed
    {
        return $this->model()::average(['column' => $this->attribute($column)] + $this->paramsToCriteria($params, $order, $limit, $offset));
    }

    /**
     * @param array<string, mixed>           $params
     * @param array<int|string, string>|null $order
     */
    public function minimum(string $column, array $params = [], ?array $order = null, ?int $limit = null, ?int $offset = null): mixed
    {
        return $this->model()::minimum(['column' => $this->attribute($column)] + $this->paramsToCriteria($params, $order, $limit, $offset));
    }

    /**
     * @param array<string, mixed>           $params
     * @param array<int|string, string>|null $order
     */
    public function maximum(string $column, array $params = [], ?array $order = null, ?int $limit = null, ?int $offset = null): mixed
    {
        return $this->model()::maximum(['column' => $this->attribute($column)] + $this->paramsToCriteria($params, $order, $limit, $offset));
    }

    public function firstOrNew(array $params = [], bool $create = false, bool $withTransaction = false): ModelInterface
    {
        $model = $this->first($params);

        if ($model !== null) {
            return $model;
        }

        $class = $this->model();
        $model = new $class();

        $data = [];
        foreach ($params as $key => $param) {
            $data[$this->attribute($key)] = $param;
        }
        $model->assign($data);

        if ($create && !$this->create($model, $withTransaction)) {
            throw new TransactionException(__METHOD__ . ': can\'t create model: ' . $model::class);
        }

        return $model;
    }

    public function firstOrCreate(array $params = [], bool $withTransaction = false): ModelInterface
    {
        return $this->firstOrNew($params, true, $withTransaction);
    }

    public function each(array $params = [], ?int $start = null, ?int $end = null, int $pad = 100, ?array $order = null): Generator
    {
        $start ??= 0;

        if ($pad < 1 || ($end !== null && $start >= $end)) {
            return;
        }

        $index = 0;

        for ($offset = $start; $end === null || $offset < $end; $offset += $pad) {
            $limit = $end === null ? $pad : min($pad, $end - $offset);
            $count = 0;

            foreach ($this->model()::find($this->paramsToCriteria($params, $order, $limit, $offset)) as $model) {
                $count++;

                /** @var ModelInterface<mixed> $model */
                yield $index++ => $model;
            }

            // A partial page is the last one.
            if ($count < $limit) {
                return;
            }
        }
    }

    public function create(ModelInterface|array $value, bool $withTransaction = true): bool
    {
        return $this->write(is_array($value) ? $value : [$value], 'create', $withTransaction);
    }

    public function save(ModelInterface|array $value, bool $withTransaction = true): bool
    {
        return $this->write(is_array($value) ? $value : [$value], 'save', $withTransaction);
    }

    public function update(ModelInterface|array $value, bool $withTransaction = true): bool
    {
        return $this->write(is_array($value) ? $value : [$value], 'update', $withTransaction);
    }

    public function delete(ModelInterface|array $value, bool $withTransaction = true): bool
    {
        return $this->write(is_array($value) ? $value : [$value], 'delete', $withTransaction);
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Builds the criteria of `find()`: conditions on checked attribute names and operators, bound values.
     *
     * @param array<string, mixed>           $params
     * @param array<int|string, string>|null $orders
     *
     * @return array<int|string, mixed>
     */
    protected function paramsToCriteria(array $params = [], ?array $orders = null, ?int $limit = null, ?int $offset = null): array
    {
        $criteria = [];
        $clauses = [];
        $bind = [];
        $index = 0;

        foreach ($params as $key => $value) {
            $column = '[' . $this->attribute((string) $key) . ']';
            $placeholder = 'p' . $index++;
            $operator = null;

            if (is_array($value) && array_key_exists('operator', $value)) {
                $operator = is_string($value['operator']) ? strtoupper(trim($value['operator'])) : '';
                $value = $value['value'] ?? null;

                if (!in_array($operator, self::OPERATORS, true)) {
                    throw new InvalidArgumentException(static::class . ': unknown operator "' . $operator . '".');
                }
            }

            $operator ??= match (true) {
                $value === null  => 'IS NULL',
                is_array($value) => 'IN',
                default          => '=',
            };

            if ($operator === 'IS NULL' || $operator === 'IS NOT NULL') {
                $clauses[] = "$column $operator";
            } elseif ($operator === 'IN' || $operator === 'NOT IN') {
                $clauses[] = "$column $operator ({{$placeholder}:array})";
                $bind[$placeholder] = is_array($value) ? array_values($value) : [$value];
            } else {
                $clauses[] = "$column $operator :$placeholder:";
                $bind[$placeholder] = $value;
            }
        }

        if ($clauses !== []) {
            $criteria = [implode(' AND ', $clauses), 'bind' => $bind];
        }

        if ($orders !== null && $orders !== []) {
            $sorts = [];
            foreach ($orders as $key => $direction) {
                [$attribute, $direction] = is_int($key) ? [$direction, 'ASC'] : [$key, strtoupper($direction)];

                if ($direction !== 'ASC' && $direction !== 'DESC') {
                    throw new InvalidArgumentException(static::class . ': unknown sort direction "' . $direction . '".');
                }

                $sorts[] = '[' . $this->attribute($attribute) . '] ' . $direction;
            }

            $criteria['order'] = implode(', ', $sorts);
        }

        if ($limit !== null) {
            $criteria['limit'] = $limit;
        }
        if ($offset !== null) {
            $criteria['offset'] = $offset;
        }

        return $criteria;
    }

    private static function resultset(mixed $found): ResultsetInterface
    {
        return $found instanceof ResultsetInterface ? $found : throw new RuntimeException('find() did not return a resultset.');
    }

    /**
     * @return class-string<ModelInterface<mixed>>
     */
    protected function model(): string
    {
        /** @var class-string<ModelInterface<mixed>> */
        return $this->modelClass;
    }

    /**
     * Checks an attribute name of the model.
     */
    protected function attribute(string $name): string
    {
        if ($this->attributes === null) {
            $class = $this->model();
            $model = new $class();
            $metaData = $model->getModelsMetaData();
            $map = $metaData->getColumnMap($model);

            $this->attributes = array_fill_keys(is_array($map) ? array_values($map) : $metaData->getAttributes($model), true);
        }

        if (!isset($this->attributes[$name])) {
            throw new InvalidArgumentException(static::class . ': unknown attribute "' . $name . '" of ' . $this->model() . '.');
        }

        return $name;
    }

    /**
     * @param array<ModelInterface<mixed>> $models
     */
    private function write(array $models, string $method, bool $withTransaction): bool
    {
        $this->messages = [];

        if ($models === []) {
            return true;
        }

        $transaction = null;
        if ($withTransaction) {
            /** @var TransactionManager $manager */
            $manager = $this->getDI()->getShared(Services::TRANSACTION_MANAGER);
            $transaction = $manager->get();
        }

        try {
            foreach ($models as $model) {
                if ($transaction !== null) {
                    $model->setTransaction($transaction);
                }

                if ($model->$method() === false) {
                    /** @var list<MessageInterface> $messages */
                    $messages = $model->getMessages();
                    array_push($this->messages, ...$messages);
                }
            }

            if ($this->messages !== []) {
                throw new TransactionException(reset($models)::class . ':' . $method . ': failed. Show ' . static::class . '::getMessages().');
            }

            if ($transaction !== null && !$transaction->commit()) {
                throw new TransactionException('Commit failed.');
            }

            return true;
        } catch (Throwable $e) {
            if ($transaction !== null) {
                try {
                    $transaction->rollback();
                } catch (Throwable) {
                    // the rollback exception of Phalcon: the messages are read below
                }

                /** @var list<MessageInterface> $messages */
                $messages = $transaction->getMessages();
                array_push($this->messages, ...$messages);
            }

            $this->messages[] = $e->getMessage();

            return false;
        }
    }
}

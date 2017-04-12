<?php

namespace Neutrino\Repositories;

use Neutrino\Interfaces\Repositories\RepositoryInterface;
use Neutrino\Repositories\Exceptions\TransactionException;
use Phalcon\Db\Column;
use Phalcon\Di\Injectable;
use Phalcon\Mvc\Model\Transaction;
use Phalcon\Text;

/**
 * Class Repository
 *
 * @package Neutrino\Repositories
 */
abstract class Repository extends Injectable implements RepositoryInterface
{
    /** @var \Neutrino\Model */
    protected $modelClass;

    /** @var string */
    protected $alias;

    /** @var \Phalcon\Mvc\Model\MessageInterface[] */
    protected $messages = [];

    /** @var \Phalcon\Mvc\Model\Query[] */
    protected $queries = [];

    /**
     * Repository constructor.
     *
     * @param null $modelClass
     *
     * @throws \RuntimeException
     */
    public function __construct($modelClass = null)
    {
        $this->modelClass = is_null($modelClass) ? $this->modelClass : $modelClass;

        if (empty($this->modelClass)) {
            throw new \RuntimeException(static::class . ' must have a $modelClass.');
        }

        $this->alias = Text::random(Text::RANDOM_ALPHA, 3);
    }

    /**
     * @return \Phalcon\Mvc\Model\ResultsetInterface|\Neutrino\Model[]
     */
    public function all()
    {
        return $this->getQuery($this->createPhql())->execute();
    }

    /**
     * @param null|array $criteria
     *
     * @return \Phalcon\Mvc\Model\ResultsetInterface|int
     */
    public function count(array $criteria = null)
    {
        $class = $this->modelClass;

        return $class::count($criteria);
    }

    /**
     * @param array      $params
     * @param array|null $orders
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return \Neutrino\Model[]|\Phalcon\Mvc\Model\ResultsetInterface
     */
    public function find(array $params = [], array $orders = null, $limit = null, $offset = null)
    {
        return $this
            ->getQuery($this->createPhql(null, $params, $orders, $limit, $offset))
            ->execute(
                $_params = $this->createBindParams($params, $limit, $offset),
                $this->createBindType($_params)
            );
    }

    /**
     * @param array      $params
     * @param array|null $orders
     * @param int|null   $offset
     *
     * @return \Neutrino\Model|\Phalcon\Mvc\Model
     */
    public function first(array $params = [], array $orders = null, $offset = null)
    {
        $query = $this->getQuery($this->createPhql(null, $params, $orders, null, $offset));

        $query->setUniqueRow(true);

        return $query->execute(
            $_params = $this->createBindParams($params, null, $offset),
            $this->createBindType($_params)
        );
    }

    /**
     * @param array $params
     * @param bool  $create
     * @param bool  $withTransaction
     *
     * @return \Neutrino\Model|\Phalcon\Mvc\Model
     * @throws \Neutrino\Repositories\Exceptions\TransactionException
     */
    public function firstOrNew(array $params = [], $create = false, $withTransaction = false)
    {
        $model = $this->first($params);

        if ($model === false) {
            $class = $this->modelClass;

            $model = new $class;

            foreach ($params as $key => $param) {
                $model->$key = $param;
            }

            if ($create && $this->create($model, $withTransaction) === false) {
                throw new TransactionException(__METHOD__ . ': can\'t create model : ' . get_class($model));
            };
        }

        return $model;
    }

    /**
     * @param array $params
     * @param bool  $withTransaction
     *
     * @return \Neutrino\Model|\Phalcon\Mvc\Model
     */
    public function firstOrCreate(array $params = [], $withTransaction = false)
    {
        return $this->firstOrNew($params, true, $withTransaction);
    }

    /**
     * @param \Neutrino\Model|\Neutrino\Model[] $value
     * @param bool                              $withTransaction
     *
     * @return bool
     */
    public function create($value, $withTransaction = true)
    {
        if ($withTransaction) {
            return $this->transactionCall(is_array($value) ? $value : [$value], __FUNCTION__);
        }

        return $this->basicCall(is_array($value) ? $value : [$value], __FUNCTION__);
    }

    /**
     * @param \Neutrino\Model|\Neutrino\Model[] $value
     * @param bool                              $withTransaction
     *
     * @return bool
     */
    public function save($value, $withTransaction = true)
    {
        if ($withTransaction) {
            return $this->transactionCall(is_array($value) ? $value : [$value], __FUNCTION__);
        }

        return $this->basicCall(is_array($value) ? $value : [$value], __FUNCTION__);
    }

    /**
     * @param \Neutrino\Model|\Neutrino\Model[] $value
     * @param bool                              $withTransaction
     *
     * @return bool
     */
    public function update($value, $withTransaction = true)
    {
        if ($withTransaction) {
            return $this->transactionCall(is_array($value) ? $value : [$value], __FUNCTION__);
        }

        return $this->basicCall(is_array($value) ? $value : [$value], __FUNCTION__);
    }

    /**
     * @param \Neutrino\Model|\Neutrino\Model[] $value
     * @param bool                              $withTransaction
     *
     * @return bool
     */
    public function delete($value, $withTransaction = true)
    {
        if ($withTransaction) {
            return $this->transactionCall(is_array($value) ? $value : [$value], __FUNCTION__);
        }

        return $this->basicCall(is_array($value) ? $value : [$value], __FUNCTION__);
    }

    /**
     * Use as :
     * foreach($repository->each() as $model){
     *     // ... do some stuff
     * }
     *
     * @param array    $params
     * @param null|int $start
     * @param null|int $end
     * @param int      $pad
     *
     * @return \Generator|\Neutrino\Model[]
     */
    public function each(array $params = [], $start = null, $end = null, $pad = 100)
    {
        if (is_null($start)) {
            $start = 0;
        }

        if (is_null($end)) {
            $end = INF;
        }

        if ($start >= $end) {
            return;
        }

        $query = $this->getQuery($this->createPhql(null, $params, null, true, true));

        $nb = ceil(($end - $start) / $pad);
        $idx = 0;
        for ($i = 0; $i < $nb; $i++) {
            $results = $query->execute(
                $_params = $this->createBindParams($params, $pad, ($start + ($pad * $i))),
                $this->createBindType($_params)
            );

            $empty = true;

            foreach ($results as $result) {
                $empty = false;

                yield $idx => $result;

                $idx++;
            }

            if ($empty) {
                break;
            }
        }
    }

    /**
     * @return \Phalcon\Mvc\Model\MessageInterface[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param null|string|array $columns
     * @param array             $params
     * @param array|null        $orders
     * @param null|int          $limit
     * @param null              $offset
     *
     * @return string
     */
    protected function createPhql($columns = null, array $params = [], array $orders = null, $limit = null, $offset = null)
    {
        $phql = 'SELECT';

        if (empty($columns)) {
            $phql .= ' *';
        } else {
            if (is_string($columns)) {
                $phql .= ' ' . $columns;
            } elseif (is_array($columns)) {
                $phql .= $this->alias . '.' . implode(', ' . $this->alias . '.', $columns);
            } else {
                $phql .= ' *';
            }
        }

        $phql .= " FROM {$this->modelClass} AS {$this->alias}";

        foreach ($params as $key => $where) {
            if (is_array($where)) {
                $keys = array_map(function ($k) use ($key) {
                    return $key . '_' . $k;
                }, array_keys($where));

                $clauses[] = "{$this->alias}.$key IN (:" . implode(':, :', $keys) . ':)';
            } elseif (is_string($where)) {
                $clauses[] = "{$this->alias}.$key LIKE :$key:";
            } else {
                $clauses[] = "{$this->alias}.$key = :$key:";
            }
        }

        if (!empty($clauses)) {
            $phql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        if (!empty($orders)) {
            $_orders = [];
            foreach ($orders as $key => $order) {
                if (is_int($key)) {
                    $key = $order;
                    $order = 'ASC';
                }
                $_orders[] = "{$this->alias}.$key $order";
            }

            $phql .= ' ORDER BY ' . implode(', ', $_orders);
        }

        if (isset($limit)) {
            $phql .= " LIMIT :{$this->alias}_limit_phql:";
        }

        if (isset($offset)) {
            $phql .= " OFFSET :{$this->alias}_offset_phql:";
        }

        return $phql;
    }

    /**
     * @param array $params
     * @param null  $limit
     * @param null  $offset
     *
     * @return array
     */
    protected function createBindParams(array $params = [], $limit = null, $offset = null)
    {
        $_params = [];

        if (isset($limit)) {
            $_params["{$this->alias}_limit_phql"] = $limit;
        }

        if (isset($offset)) {
            $_params["{$this->alias}_offset_phql"] = $offset;
        }

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $val) {
                    $_params[$key . '_' . $k] = $val;
                }
            } else {
                $_params[$key] = $value;
            }
        }

        return $_params;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    protected function createBindType(array $params = [])
    {
        $types = [];

        foreach ($params as $param => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    $types[$key] = $this->getBindType($val);
                }
            } else {
                $types[$param] = $this->getBindType($value);
            }
        }

        return $types;
    }

    /**
     * @param null|bool|int|float|double|string $value
     *
     * @return int
     */
    protected function getBindType($value)
    {
        if (is_null($value)) {
            return Column::BIND_PARAM_NULL;
        } elseif (is_bool($value)) {
            return Column::BIND_PARAM_BOOL;
        } elseif (is_int($value)) {
            return Column::BIND_PARAM_INT;
        } elseif (is_float($value) || is_double($value)) {
            return Column::BIND_PARAM_DECIMAL;
        } else {
            return Column::BIND_PARAM_STR;
        }
    }

    /**
     * @param string $phql
     *
     * @return \Phalcon\Mvc\Model\QueryInterface
     */
    protected function getQuery($phql)
    {
        if (!isset($this->queries[$phql])) {
            $this->queries[$phql] = $this->modelsManager->createQuery($phql);
        }

        return $this->queries[$phql];
    }

    /**
     * @param array $values
     * @param       $method
     *
     * @return bool
     */
    protected function basicCall(array $values, $method)
    {
        try {
            $this->messages = [];

            foreach ($values as $item) {
                if ($item->$method() === false) {
                    $this->messages = array_merge($this->messages, $item->getMessages());
                }
            }

            if (!empty($this->messages)) {
                throw new TransactionException(get_class(arr_get($values, 0)) . ':' . $method . ': failed. Show ' . static::class . '::getMessages().');
            }
        } catch (\Exception $e) {
            $this->messages[] = $e->getMessage();

            return false;
        }

        return true;
    }

    /**
     * @param \Neutrino\Model[]|\Phalcon\Mvc\Model[] $values
     * @param string                                 $method
     *
     * @return bool
     */
    protected function transactionCall(array $values, $method)
    {
        if (empty($values)) {
            return true;
        }

        /** @var Transaction $tx */
        $tx = $this->getDI()->getShared(Transaction\Manager::class)->get();

        try {
            $this->messages = [];

            foreach ($values as $item) {

                $item->setTransaction($tx);

                if ($item->$method() === false) {
                    $this->messages = array_merge($this->messages, $item->getMessages());

                    $tx->rollback();
                }
            }

            if (!empty($this->messages)) {
                throw new TransactionException(get_class(arr_get($values, 0)) . ':' . $method . ': failed. Show ' . static::class . '::getMessages().');
            }

            if ($tx->commit() === false) {
                throw new TransactionException('Commit failed.');
            }

            return true;
        } catch (\Exception $e) {
            $this->messages[] = $e->getMessage();
            if (!is_null($messages = $tx->getMessages())) {
                $this->messages = array_merge($this->messages, $messages);
            }

            return false;
        }
    }
}

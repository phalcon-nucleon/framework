<?php

namespace Neutrino\Repositories;

use Neutrino\Interfaces\Repositories\RepositoryInterface;
use Neutrino\Repositories\Exceptions\TransactionException;
use Phalcon\Di\Injectable;

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

        $this->alias = \Phalcon\Text::random(\Phalcon\Text::RANDOM_ALPHA, 3);
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
            ->execute($this->createBindParams($params, $limit, $offset));
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

        return $query->execute($this->createBindParams($params, null, $offset));
    }

    /**
     * @param array $params
     * @param bool  $create
     *
     * @param bool  $create
     *
     * @return \Neutrino\Model|\Phalcon\Mvc\Model
     * @throws \Neutrino\Repositories\Exceptions\TransactionException
     */
    public function firstOrNew(array $params = [], $create = false)
    {
        $model = $this->first($params);

        if ($model === false) {
            $class = $this->modelClass;

            $model = new $class;

            foreach ($params as $key => $param) {
                $model->$key = $param;
            }

            if ($create && $this->create($model) === false) {
                throw new TransactionException(__METHOD__ . ': can\'t create model : ' . get_class($model));
            };
        }

        return $model;
    }

    /**
     * @param array $params
     *
     * @return \Neutrino\Model|\Phalcon\Mvc\Model
     * @throws \Neutrino\Repositories\Exceptions\TransactionException
     */
    public function firstOrCreate(array $params = [])
    {
        return $this->firstOrNew($params, true);
    }

    /**
     * @param \Neutrino\Model|\Neutrino\Model[] $value
     *
     * @return bool
     */
    public function create($value)
    {
        return $this->transactionCall(is_array($value) ? $value : [$value], __FUNCTION__);
    }

    /**
     * @param \Neutrino\Model|\Neutrino\Model[] $value
     *
     * @return bool
     */
    public function save($value)
    {
        return $this->transactionCall(is_array($value) ? $value : [$value], __FUNCTION__);
    }

    /**
     * @param \Neutrino\Model|\Neutrino\Model[] $value
     *
     * @return bool
     */
    public function update($value)
    {
        return $this->transactionCall(is_array($value) ? $value : [$value], __FUNCTION__);
    }

    /**
     * @param \Neutrino\Model|\Neutrino\Model[] $value
     *
     * @return bool
     */
    public function delete($value)
    {
        return $this->transactionCall(is_array($value) ? $value : [$value], __FUNCTION__);
    }

    /**
     * Use as :
     * foreach($repository->each() as $model){
     *     // ... do some stuff
     * }
     *
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
        for ($i = 0; $i < $nb; $i++) {
            $results = $query->execute($this->createBindParams($params, $pad, ($start + ($pad * $i))));

            $empty = true;

            foreach ($results as $result) {
                $empty = false;

                yield $result;
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

        if(empty($columns)) {
            $phql .= ' *';
        } else {
            if(is_string($columns)){
                $phql .= ' ' . $columns;
            } elseif(is_array($columns)) {
                $phql .= $this->alias . '.' . implode(', ' . $this->alias . '.', $columns);
            } else {
                $phql .= ' *';
            }
        }

        $phql .= " FROM {$this->modelClass} AS {$this->alias}";

        foreach ($params as $key => $where) {
            $operator = '=';

            if (is_array($where)) {
                $operator = 'IN';
            } elseif (is_string($where)) {
                $operator = 'LIKE';
            }

            $clauses[] = "{$this->alias}.$key $operator :$key:";
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
        if (isset($limit)) {
            $params["{$this->alias}_limit_phql"] = $limit;
        }

        if (isset($offset)) {
            $params["{$this->alias}_offset_phql"] = $offset;
        }

        return $params;
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

        try {
            $this->db->begin();

            $this->messages = [];

            foreach ($values as $item) {
                if ($item->$method() === false) {
                    $this->messages = array_merge($this->messages, $item->getMessages());
                }
            }

            if (!empty($this->messages)) {
                throw new TransactionException(get_class(arr_get($values, 0)) . ':' . $method . ': failed. Show ' . static::class . '::getMessages().');
            }

            if ($this->db->commit() === false) {
                throw new TransactionException('Commit failed.');
            }

            return true;
        } catch (\Exception $e) {
            $this->messages[] = $e->getMessage();

            $this->db->rollback();

            return false;
        }
    }
}

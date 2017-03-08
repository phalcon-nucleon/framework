<?php

namespace Neutrino\Repositories;

use Neutrino\Interfaces\Repositories\RepositoryInterface;
use Neutrino\Repositories\Exceptions\TransactionException;
use Phalcon\Di\Injectable;

abstract class Repository extends Injectable implements RepositoryInterface
{
    /** @var \Neutrino\Model */
    protected $modelClass;

    /** @var \Phalcon\Mvc\Model\MessageInterface[] */
    protected $messages = [];

    protected static $queries = [];

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
    }

    /**
     * @return \Phalcon\Mvc\Model\ResultsetInterface|\Neutrino\Model[]
     */
    public function all()
    {
        return $this->createQuery([])->execute();
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
     * @param array|string|int $criteria
     *
     * @return \Phalcon\Mvc\Model\ResultsetInterface|\Neutrino\Model[]
     */
    public function find($criteria = null)
    {
        $class = $this->modelClass;

        return $class::find($criteria);
    }

    /**
     * @param array $params
     *
     * @return \Neutrino\Model|\Phalcon\Mvc\Model
     */
    public function first(array $params = [])
    {
        $query = $this->createQuery($params, 1);

        $result = $query->execute($params);

        return $result->getFirst();
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
        $class = $this->modelClass;

        $model = $this->first($params);

        if ($model === false) {
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
     *
     *     $model->save();
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
    public function each(array $params = [], $start = null, $end = null, $pad = 20)
    {
        if (is_null($start)) {
            $start = 0;
        }

        if (is_null($end)) {
            $end = $this->count();
        }

        if ($start >= $end) {
            return;
        }

        $phql = "SELECT * FROM {$this->modelClass}";

        foreach ($params as $key => $value) {
            $clauses[] = "$key = :$key:";
        }

        if (isset($clauses)) {
            $phql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        $nb = ($end - $start) / $pad;
        for ($i = 0; $i < $nb; $i++) {
            $query = $this->modelsManager->createQuery($phql . " LIMIT " . ($start + ($pad * $i)) . ', ' . $pad);

            $results = $query->execute($params);

            foreach ($results as $result) {
                yield $result;
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
     * @param array    $wheres
     * @param null|int $limit
     *
     * @return \Phalcon\Mvc\Model\QueryInterface
     */
    protected function createQuery($wheres, $limit = null)
    {
        $phql = "SELECT * FROM {$this->modelClass}";

        foreach ($wheres as $key => $where) {
            $clauses[] = "$key = :$key:";
        }

        if (!empty($clauses)) {
            $phql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        if (!empty($limit)) {
            $phql .= " LIMIT $limit";
        }

        return $this->getQuery($phql);
    }

    /**
     * @param string $phql
     *
     * @return \Phalcon\Mvc\Model\QueryInterface
     */
    protected function getQuery($phql)
    {
        if (!isset(self::$queries[$phql])) {
            self::$queries[$phql] = $this->modelsManager->createQuery($phql);
        }

        return self::$queries[$phql];
    }

    /**
     * @return \Phalcon\Mvc\Model\Criteria
     */
    protected function query()
    {
        $class = $this->modelClass;

        return $class::query();
    }

    protected function paramsToCriteria(array $params)
    {
        $criteria = [];

        foreach ($params as $key => $param) {
            $criteria['conditions'][] = "$key = :$key:";
            $criteria['bind'][$key]   = $param;
        }
        $criteria['conditions'] = implode(' AND ', $criteria['conditions']);

        return $criteria;
    }

    /**
     * @param \Neutrino\Model[]|\Phalcon\Mvc\Model[] $values
     * @param string                                 $method
     *
     * @return bool
     */
    protected function transactionCall(array $values, $method)
    {
        $this->db->begin();

        try {
            foreach ($values as $item) {
                if ($item->$method() === false) {
                    $this->messages = $item->getMessages();
                    throw new TransactionException(get_class($item) . ':' . $method . ': failed. Show ' . get_class($this) . '::getMessages().');
                };
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

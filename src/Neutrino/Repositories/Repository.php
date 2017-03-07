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
        $class = $this->modelClass;

        return $class::find();
    }

    /**
     * @param null|array $criteria
     *
     * @return \Phalcon\Mvc\Model\ResultsetInterface
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
     * @param array|string|int $criteria
     *
     * @return \Neutrino\Model|\Phalcon\Mvc\Model
     */
    public function first($criteria = null)
    {
        $class = $this->modelClass;

        return $class::findFirst($criteria);
    }

    /**
     * @param array $params
     *
     * @return \Neutrino\Model|\Phalcon\Mvc\Model
     */
    public function firstOrNew(array $params = [])
    {
        $class = $this->modelClass;

        $model = $class::findFirst($this->paramsToCriteria($params));

        if ($model === false) {
            $model = new $class;

            foreach ($params as $key => $param) {
                $model->$key = $param;
            }
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
        $class = $this->modelClass;

        $model = $class::findFirst($this->paramsToCriteria($params));

        if ($model === false) {
            $model = new $class;

            foreach ($params as $key => $param) {
                $model->$key = $param;
            }

            if ($this->create($model) === false) {
                throw new TransactionException(__METHOD__ . ': can\'t create model : ' . get_class($model));
            };
        }

        return $model;
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
     * @return \Phalcon\Mvc\Model\MessageInterface[]
     */
    public function getMessages()
    {
        return $this->messages;
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
            $criteria['bind'][$key] = $params;
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
            $this->db->rollback();

            return false;
        }
    }
}
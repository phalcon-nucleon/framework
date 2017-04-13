<?php

namespace Neutrino\Repositories;

use Neutrino\Interfaces\Repositories\RepositoryInterface;
use Neutrino\Repositories\Exceptions\TransactionException;
use Phalcon\Di\Injectable;
use Phalcon\Mvc\Model\Transaction;

/**
 * Class Repository
 *
 * @package Neutrino\Repositories
 */
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
     * @return \Phalcon\Mvc\Model\MessageInterface[]
     */
    public function getMessages()
    {
        return $this->messages;
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

        /** @var \Phalcon\Mvc\Model\Transaction $tx */
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

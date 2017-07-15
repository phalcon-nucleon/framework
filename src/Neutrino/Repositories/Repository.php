<?php

namespace Neutrino\Repositories;

use Neutrino\Interfaces\Repositories\RepositoryInterface;
use Neutrino\Repositories\Exceptions\TransactionException;
use Neutrino\Support\Arr;
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
     * @inheritdoc
     */
    public function all()
    {
        $class = $this->modelClass;

        return $class::find();
    }

    /**
     * @inheritdoc
     */
    public function count(array $params = null)
    {
        $class = $this->modelClass;

        return $class::count($this->paramsToCriteria($params));
    }

    /**
     * @inheritdoc
     */
    public function find(array $params = [], array $order = null, $limit = null, $offset = null)
    {
        $class = $this->modelClass;

        return $class::find($this->paramsToCriteria($params, $order, $limit, $offset));
    }

    /**
     * @inheritdoc
     */
    public function first(array $params = [], array $order = null)
    {
        $class = $this->modelClass;

        return $class::findFirst($this->paramsToCriteria($params, $order));
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
     * @inheritdoc
     */
    public function each(array $params = [], $start = null, $end = null, $pad = 100, array $order = null)
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

        $class = $this->modelClass;

        $nb = ceil(($end - $start) / $pad);
        $idx = 0;
        $page = 0;
        do {
            $finish = true;

            $models = $class::find($this->paramsToCriteria($params, $order, $pad, ($start + ($pad * $page))));

            foreach ($models as $model){
                $finish = false;

                yield $idx => $model;

                $idx++;
            }

            $page++;

            if($page >= $nb){
                $finish = true;
            }
        } while (!$finish);
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
     * @param array      $params
     * @param array|null $orders
     * @param null       $limit
     * @param null       $offset
     *
     * @return array
     */
    protected function paramsToCriteria(array $params = null, array $orders = null, $limit = null, $offset = null)
    {
        $criteria = [];

        if(!empty($params)){
            $clauses = [];

            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    $clauses[] = "$key IN ({{$key}:array})";
                } elseif (is_string($value)) {
                    $clauses[] = "$key LIKE :$key:";
                } else {
                    $clauses[] = "$key = :$key:";
                }
            }

            $criteria = [
                implode(' AND ', $clauses),
                'bind' => $params
            ];
        }

        if (!empty($orders)) {
            $_orders = [];
            foreach ($orders as $key => $order) {
                if (is_int($key)) {
                    $key = $order;
                    $order = 'ASC';
                }
                $_orders[] = "$key $order";
            }

            $criteria['order'] = implode(', ', $_orders);
        }

        if (isset($limit)) {
            $criteria['limit'] = $limit;
        }

        if (isset($offset)) {
            $criteria['offset'] = $offset;
        }

        return $criteria;
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
                throw new TransactionException(get_class(Arr::fetch($values, 0)) . ':' . $method . ': failed. Show ' . static::class . '::getMessages().');
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
                }
            }

            if (!empty($this->messages)) {
                throw new TransactionException(get_class(Arr::fetch($values, 0)) . ':' . $method . ': failed. Show ' . static::class . '::getMessages().');
            }

            if ($tx->commit() === false) {
                throw new TransactionException('Commit failed.');
            }

            return true;
        } catch (\Exception $e) {
            $tx->rollback();

            $this->messages[] = $e->getMessage();
            if (!is_null($messages = $tx->getMessages())) {
                $this->messages = array_merge($this->messages, $messages);
            }

            return false;
        }
    }
}

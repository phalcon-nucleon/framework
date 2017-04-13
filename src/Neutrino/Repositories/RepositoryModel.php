<?php

namespace Neutrino\Repositories;

use Neutrino\Interfaces\Repositories\RepositoryInterface;
use Neutrino\Repositories\Exceptions\TransactionException;

/**
 * Class RepositoryModel
 *
 * @package Neutrino\Repositories
 */
abstract class RepositoryModel extends Repository
{
    /** @var \Neutrino\Model */
    protected $modelClass;

    /** @var \Phalcon\Mvc\Model\MessageInterface[] */
    protected $messages = [];

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
     * @param array      $params
     * @param array|null $order
     * @param null       $limit
     * @param null       $offset
     *
     * @return array
     */
    protected function paramsToCriteria(array $params = [], array $order = null, $limit = null, $offset = null)
    {
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
}
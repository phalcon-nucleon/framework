<?php

namespace Neutrino\Repositories;

use Phalcon\Db\Column;
use Phalcon\Text;

/**
 * Class Repository
 *
 * @package Neutrino\Repositories
 */
abstract class RepositoryPhql extends Repository
{
    /** @var string */
    protected $alias;

    /** @var \Phalcon\Mvc\Model\Query[] */
    protected $queries = [];

    /**
     * @inheritdoc
     */
    public function __construct($modelClass = null)
    {
        parent::__construct($modelClass);

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

        $query = $this->getQuery($this->createPhql(null, $params, $order, true, true));

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
}

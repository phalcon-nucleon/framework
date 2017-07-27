<?php

namespace Neutrino\Database\Schema;

use Neutrino\Support\Fluent\Fluentable;
use Neutrino\Support\Fluent\Fluentize;
use Phalcon\Db\Column as DbColumn;

/**
 * Class Column
 *
 * @package     Neutrino\Database\Schema
 *
 * @method $this index()
 * @method $this unique()
 */
class Column extends DbColumn implements Fluentable
{
    use Fluentize {
        __construct as fluentConstruct;
    }

    public function __construct($name = null, $attributes = null)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException();
        }
        if (empty($attributes)) {
            throw new \InvalidArgumentException();
        }

        parent::__construct($name, $attributes);
        $this->fluentConstruct($attributes);
    }

    /**
     * Place the column "after" another column (MySQL Only)
     */
    public function setAfter($column)
    {
        $this->_after = $column;
        $this->attributes['after'] = $column;

        return $this;
    }

    public function after($column)
    {
        return $this->setAfter($column);
    }

    /**
     * Specify a "default" value for the column
     *
     * @param $default
     *
     * @return $this
     */
    public function setDefault($default)
    {
        $this->_default = $default;

        return $this;
    }

    /**
     * Place the column "first" in the table (MySQL Only)
     *
     * @param bool $first
     *
     * @return $this
     */
    public function setFirst($first = true)
    {
        $this->_first = $first;
        $this->attributes['first'] = $first;

        return $this;
    }

    public function first($first = true)
    {
        return $this->setFirst($first);
    }

    /**
     * Allow NULL values to be inserted into the column
     *
     * @param bool $nullable
     *
     * @return $this
     */
    public function setNullable($nullable = true)
    {
        $this->_notNull = !$nullable;
        $this->attributes['nullable'] = $nullable;

        return $this;
    }

    public function nullable($nullable = true)
    {
        return $this->setNullable($nullable);
    }

    /**
     * Set integer columns to UNSIGNED
     *
     * @param bool $unsigned
     *
     * @return $this
     */
    public function setUnsigned($unsigned = true)
    {
        $this->_unsigned = $unsigned;
        $this->attributes['unsigned'] = true;

        return $this;
    }

    public function unsigned($unsigned = true)
    {
        return $this->setUnsigned($unsigned);
    }

    /**
     * @param bool $primary
     *
     * @return $this
     */
    public function setPrimary($primary = true)
    {
        $this->_primary = $primary;
        $this->attributes['primary'] = true;

        return $this;
    }

    public function primary($primary = true)
    {
        return $this->setPrimary($primary);
    }

    /**
     * @return bool
     */
    public function isPrimary()
    {
        return (bool)(parent::isPrimary() || $this->get('primary'));
    }
}

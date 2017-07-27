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
 * @method $this primary()
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

        return $this;
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

        return $this;
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

        return $this;
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

        return $this;
    }

    /**
     * @param bool $primary
     *
     * @return $this
     */
    public function setPrimary($primary = true)
    {
        $this->_primary = $primary;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPrimary()
    {
        return (bool)(parent::isPrimary() || $this->get('primary'));
    }
}

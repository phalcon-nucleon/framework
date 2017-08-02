<?php

namespace Neutrino\Database\Schema\Dialect;

use Neutrino\Database\Schema;
use Neutrino\Support\Fluent;
use \Phalcon\Db\Dialect;

/**
 * Class Mysql
 *
 * @package Neutrino\Database\Schema\Dialect
 */
class Sqlite extends Dialect\Sqlite implements Schema\DialectInterface
{
    use Schema\DialectTrait;

    /**
     * Create the column type definition for a tinyInteger type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeTinyInteger(Fluent $column)
    {
        return $this->typeInteger($column);
    }

    /**
     * Create the column type definition for a smallInteger type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeSmallInteger(Fluent $column)
    {
        return $this->typeInteger($column);
    }

    /**
     * Create the column type definition for a mediumInteger type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeMediumInteger(Fluent $column)
    {
        return $this->typeInteger($column);
    }


    /**
     * Create the column type definition for a mediumText type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeMediumText(Fluent $column)
    {
        return $this->typeText($column);
    }

    /**
     * Create the column type definition for a longText type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeLongText(Fluent $column)
    {
        return $this->typeText($column);
    }

    /**
     * Create the column type definition for a dateTimeTz type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeDateTimeTz(Fluent $column)
    {
        return $this->typeDateTime($column);
    }

    /**
     * Create the column type definition for a timeTz type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeTimeTz(Fluent $column)
    {
        return $this->typeTime($column);
    }

    /**
     * Create the column type definition for a timestampTz type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeTimestampTz(Fluent $column)
    {
        return $this->typeTimestamp($column);
    }

    /**
     * Create the column type definition for a enum type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeEnum(Fluent $column)
    {
        return $this->typeString($column);
    }
}

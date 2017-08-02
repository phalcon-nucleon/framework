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
class Mysql extends Dialect\Mysql implements Schema\DialectInterface
{
    use Schema\DialectTrait;

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
}

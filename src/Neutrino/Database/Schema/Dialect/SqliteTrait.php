<?php

namespace Neutrino\Database\Schema\Dialect;

use Neutrino\Database\Schema;
use Neutrino\Support\Fluent;

/**
 * Trait SqliteTrait
 *
 * @package Neutrino\Database\Schema\Dialect
 */
trait SqliteTrait
{
    use Schema\DialectTrait;

    /**
     * Get SQL Enable foreign key constraints.
     *
     * @return string
     */
    public function enableForeignKeyConstraints()
    {
        return 'PRAGMA foreign_keys = ON;';
    }

    /**
     * Get SQL Disable foreign key constraints.
     *
     * @return string
     */
    public function disableForeignKeyConstraints()
    {
        return 'PRAGMA foreign_keys = OFF;';
    }

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

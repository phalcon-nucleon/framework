<?php

namespace Neutrino\Database\Schema;

use Neutrino\Support\Fluent;

/**
 * Class DialectInterface
 *
 * @package Neutrino\Database\Schema\Dialect
 */
interface DialectInterface extends \Phalcon\Db\DialectInterface
{
    /**
     * Get SQL Enable foreign key constraints.
     *
     * @return string
     */
    public function enableForeignKeyConstraints();

    /**
     * Get SQL Disable foreign key constraints.
     *
     * @return string
     */
    public function disableForeignKeyConstraints();

    /**
     * Get the SQL for the column data type.
     *
     * @param  \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function getType(Fluent $column);

    /**
     * Get the SQL Index type for the index data type.
     *
     * @param  \Neutrino\Support\Fluent $index
     *
     * @return array
     */
    public function getIndexType(Fluent $index);

    /**
     * Create the column type definition for a boolean type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeBoolean(Fluent $column);

    /**
     * Create the column type definition for a tinyInteger type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeTinyInteger(Fluent $column);

    /**
     * Create the column type definition for a smallInteger type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeSmallInteger(Fluent $column);

    /**
     * Create the column type definition for a mediumInteger type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeMediumInteger(Fluent $column);

    /**
     * Create the column type definition for a integer type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeInteger(Fluent $column);

    /**
     * Create the column type definition for a bigInteger type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeBigInteger(Fluent $column);

    /**
     * Create the column type definition for a decimal type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeDecimal(Fluent $column);

    /**
     * Create the column type definition for a double type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeDouble(Fluent $column);

    /**
     * Create the column type definition for a float type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeFloat(Fluent $column);

    /**
     * Create the column type definition for a json type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeJson(Fluent $column);

    /**
     * Create the column type definition for a jsonb type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeJsonb(Fluent $column);

    /**
     * Create the column type definition for a char type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeChar(Fluent $column);

    /**
     * Create the column type definition for a string type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeString(Fluent $column);

    /**
     * Create the column type definition for a mediumText type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeMediumText(Fluent $column);

    /**
     * Create the column type definition for a text type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeText(Fluent $column);

    /**
     * Create the column type definition for a longText type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeLongText(Fluent $column);

    /**
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeEnum(Fluent $column);

    /**
     * Create the column type definition for a blob type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeBlob(Fluent $column);

    /**
     * Create the column type definition for a tinyBlob type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeTinyBlob(Fluent $column);

    /**
     * Create the column type definition for a mediumBlob type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeMediumBlob(Fluent $column);

    /**
     * Create the column type definition for a longBlob type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeLongBlob(Fluent $column);

    /**
     * Create the column type definition for a date type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeDate(Fluent $column);

    /**
     * Create the column type definition for a dateTime type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeDateTime(Fluent $column);

    /**
     * Create the column type definition for a dateTimeTz type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeDateTimeTz(Fluent $column);

    /**
     * Create the column type definition for a time type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeTime(Fluent $column);

    /**
     * Create the column type definition for a timeTz type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeTimeTz(Fluent $column);

    /**
     * Create the column type definition for a timestamp type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeTimestamp(Fluent $column);

    /**
     * Create the column type definition for a timestampTz type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeTimestampTz(Fluent $column);
    /**
     * Create the column type definition for a uuid type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeUuid(Fluent $column);

    /**
     * Create the column type definition for a IP Address type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeIpAddress(Fluent $column);

    /**
     * Create the column type definition for a MAC Address type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeMacAddress(Fluent $column);
}

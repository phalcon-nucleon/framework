<?php

namespace Neutrino\Database\Schema;

use Neutrino\Support\Fluent;
use Phalcon\Db\Column;

/**
 * Class Phql
 *
 * @package     Neutrino\Database\Schema
 */
trait DialectTrait
{
    /**
     * Get the column type definition.
     *
     * @param  \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function getType(Fluent $column)
    {
        return $this->{'type' . ucfirst($column->get('type'))}($column);
    }

    /**
     * Create the column type definition for a boolean type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeBoolean(Fluent $column)
    {
        return [
            'type' => Column::TYPE_BOOLEAN
        ];
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
        return [
            'type'          => 'TINYINT',
            'typeReference' => Column::TYPE_INTEGER
        ];
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
        return [
            'type'          => 'SMALLINT',
            'typeReference' => Column::TYPE_INTEGER
        ];
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
        return [
            'type'          => 'MEDIUMINT',
            'typeReference' => Column::TYPE_INTEGER
        ];
    }

    /**
     * Create the column type definition for a integer type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeInteger(Fluent $column)
    {
        return [
            'type' => Column::TYPE_INTEGER
        ];
    }

    /**
     * Create the column type definition for a bigInteger type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeBigInteger(Fluent $column)
    {
        return [
            'type' => Column::TYPE_BIGINTEGER
        ];
    }

    /**
     * Create the column type definition for a decimal type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeDecimal(Fluent $column)
    {
        return [
            'type' => Column::TYPE_DECIMAL
        ];
    }

    /**
     * Create the column type definition for a double type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeDouble(Fluent $column)
    {
        return [
            'type' => Column::TYPE_DOUBLE
        ];
    }

    /**
     * Create the column type definition for a float type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeFloat(Fluent $column)
    {
        return [
            'type' => Column::TYPE_FLOAT
        ];
    }

    /**
     * Create the column type definition for a json type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeJson(Fluent $column)
    {
        return [
            'type' => Column::TYPE_JSON
        ];
    }

    /**
     * Create the column type definition for a jsonb type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeJsonb(Fluent $column)
    {
        return [
            'type' => Column::TYPE_JSONB
        ];
    }

    /**
     * Create the column type definition for a char type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeChar(Fluent $column)
    {
        return [
            'type' => Column::TYPE_CHAR
        ];
    }

    /**
     * Create the column type definition for a string type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeString(Fluent $column)
    {
        return [
            'type' => Column::TYPE_VARCHAR
        ];
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
        return [
            'type'          => 'MEDIUMTEXT',
            'typeReference' => Column::TYPE_TEXT
        ];
    }

    /**
     * Create the column type definition for a text type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeText(Fluent $column)
    {
        return [
            'type' => Column::TYPE_TEXT
        ];
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
        return [
            'type'          => 'LONGTEXT',
            'typeReference' => Column::TYPE_TEXT
        ];
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
        return [
            'type'          => 'ENUM',
            'typeReference' => -1,
            'typeValues'    => $column->get('values')
        ];
    }

    /**
     * Create the column type definition for a blob type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeBlob(Fluent $column)
    {
        return [
            'type' => Column::TYPE_BLOB
        ];
    }

    /**
     * Create the column type definition for a tinyBlob type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeTinyBlob(Fluent $column)
    {
        return [
            'type' => Column::TYPE_TINYBLOB
        ];
    }

    /**
     * Create the column type definition for a mediumBlob type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeMediumBlob(Fluent $column)
    {
        return [
            'type' => Column::TYPE_MEDIUMBLOB
        ];
    }

    /**
     * Create the column type definition for a longBlob type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeLongBlob(Fluent $column)
    {
        return [
            'type' => Column::TYPE_LONGBLOB
        ];
    }

    /**
     * Create the column type definition for a date type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeDate(Fluent $column)
    {
        return [
            'type' => Column::TYPE_DATE
        ];
    }

    /**
     * Create the column type definition for a dateTime type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeDateTime(Fluent $column)
    {
        return [
            'type' => Column::TYPE_DATETIME
        ];
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
     * Create the column type definition for a time type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeTime(Fluent $column)
    {
        return [
            'type'          => 'TIME',
            'typeReference' => Column::TYPE_DATETIME,
        ];
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
     * Create the column type definition for a timestamp type.
     * 
     * @param \Neutrino\Support\Fluent $column
     * 
     * @return array
     */
    public function typeTimestamp(Fluent $column)
    {
        return [
            'type' => Column::TYPE_TIMESTAMP
        ];
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
     * Create the column type definition for a uuid type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeUuid(Fluent $column)
    {
        return array_merge($this->typeChar($column), ['size' => 36]);
    }

    /**
     * Create the column type definition for a IP Address type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeIpAddress(Fluent $column)
    {
        return array_merge($this->typeString($column), ['size' => 45]);
    }

    /**
     * Create the column type definition for a MAC Address type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeMacAddress(Fluent $column)
    {
        return array_merge($this->typeString($column), ['size' => 17]);
    }
}

<?php

namespace Neutrino\Database\Schema\Grammars;

use Neutrino\Support\Fluent;
use Phalcon\Db\Column;

/**
 * Class Phql
 *
 * @package     Neutrino\Database\Schema
 */
class Phql extends Grammar
{
    /**
     * @return array
     */
    public function typeBoolean()
    {
        return [
            'type' => Column::TYPE_BOOLEAN
        ];
    }

    /**
     * @return array
     */
    public function typeTinyInteger()
    {
        return [
            'type'          => 'TINYINT',
            'typeReference' => Column::TYPE_INTEGER
        ];
    }

    /**
     * @return array
     */
    public function typeSmallInteger()
    {
        return [
            'type'          => 'SMALLINT',
            'typeReference' => Column::TYPE_INTEGER
        ];
    }

    /**
     * @return array
     */
    public function typeMediumInteger()
    {
        return [
            'type'          => 'MEDIUMINT',
            'typeReference' => Column::TYPE_INTEGER
        ];
    }

    /**
     * @return array
     */
    public function typeInteger()
    {
        return [
            'type' => Column::TYPE_INTEGER
        ];
    }

    /**
     * @return array
     */
    public function typeBigInteger()
    {
        return [
            'type' => Column::TYPE_BIGINTEGER
        ];
    }

    /**
     * @return array
     */
    public function typeDecimal()
    {
        return [
            'type' => Column::TYPE_DECIMAL
        ];
    }

    /**
     * @return array
     */
    public function typeDouble()
    {
        return [
            'type' => Column::TYPE_DOUBLE
        ];
    }

    /**
     * @return array
     */
    public function typeFloat()
    {
        return [
            'type' => Column::TYPE_FLOAT
        ];
    }

    /**
     * @return array
     */
    public function typeJson()
    {
        return [
            'type' => Column::TYPE_JSON
        ];
    }

    /**
     * @return array
     */
    public function typeJsonb()
    {
        return [
            'type' => Column::TYPE_JSONB
        ];
    }

    /**
     * @return array
     */
    public function typeChar()
    {
        return [
            'type' => Column::TYPE_CHAR
        ];
    }

    /**
     * @return array
     */
    public function typeString()
    {
        return [
            'type' => Column::TYPE_VARCHAR
        ];
    }

    /**
     * @return array
     */
    public function typeMediumText()
    {
        return [
            'type'          => 'MEDIUMTEXT',
            'typeReference' => Column::TYPE_TEXT
        ];
    }

    /**
     * @return array
     */
    public function typeText()
    {
        return [
            'type' => Column::TYPE_TEXT
        ];
    }

    /**
     * @return array
     */
    public function typeLongText()
    {
        return [
            'type'          => 'LONGTEXT',
            'typeReference' => Column::TYPE_TEXT
        ];
    }

    /**
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
     * @return array
     */
    public function typeBlob()
    {
        return [
            'type' => Column::TYPE_BLOB
        ];
    }

    /**
     * @return array
     */
    public function typeTinyBlob()
    {
        return [
            'type' => Column::TYPE_TINYBLOB
        ];
    }

    /**
     * @return array
     */
    public function typeMediumBlob()
    {
        return [
            'type' => Column::TYPE_MEDIUMBLOB
        ];
    }

    /**
     * @return array
     */
    public function typeLongBlob()
    {
        return [
            'type' => Column::TYPE_LONGBLOB
        ];
    }

    /**
     * @return array
     */
    public function typeDate()
    {
        return [
            'type' => Column::TYPE_DATE
        ];
    }

    /**
     * @return array
     */
    public function typeDateTime()
    {
        return [
            'type' => Column::TYPE_DATETIME
        ];
    }

    /**
     * @return array
     */
    public function typeDateTimeTz()
    {
        return [
            'type'          => 'DATETIME WITH TIMEZONE',
            'typeReference' => Column::TYPE_DATETIME,
        ];
    }

    /**
     * @return array
     */
    public function typeTime()
    {
        return [
            'type'          => 'TIME',
            'typeReference' => Column::TYPE_DATETIME,
        ];
    }

    /**
     * @return array
     */
    public function typeTimeTz()
    {
        return [
            'type'          => 'TIME WITH TIMEZONE',
            'typeReference' => Column::TYPE_DATETIME,
        ];
    }

    /**
     * @return array
     */
    public function typeTimestamp()
    {
        return [
            'type' => Column::TYPE_TIMESTAMP
        ];
    }

    /**
     * @return array
     */
    public function typeTimestampTz()
    {
        return [
            'type'          => 'TIMESTAMP WITH TIMEZONE',
            'typeReference' => Column::TYPE_TIMESTAMP,
        ];
    }
}

<?php

namespace Neutrino\Database\Schema\Dialect;

use Neutrino\Database\Schema;
use Neutrino\Support\Fluent;
use Phalcon\Db\Column;
use \Phalcon\Db\Dialect;

/**
 * Class Mysql
 *
 * @package Neutrino\Database\Schema\Dialect
 */
class Postgresql extends Dialect\Postgresql implements Schema\DialectInterface
{
    use Schema\DialectTrait;

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
        $values = array_map(function ($a) {
            return "'{$a}'";
        }, $column->get('values'));

        $maxlen = 0;
        foreach ($values as $value) {
            $maxlen = max($maxlen, strlen($value));
        }

        return [
            'type'          => "varchar($maxlen) check (\"{$column->get('name')}\" in (" . implode(', ', $values) . '))',
            'typeReference' => -1
        ];
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
        return [
            'type' => 'uuid',
            'typeReference' => Column::TYPE_CHAR
        ];
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
        return [
            'type' => 'inet',
            'typeReference' => Column::TYPE_VARCHAR
        ];
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
        return [
            'type' => 'macaddr',
            'typeReference' => Column::TYPE_VARCHAR
        ];
    }
}

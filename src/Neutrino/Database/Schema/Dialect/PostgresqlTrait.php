<?php

namespace Neutrino\Database\Schema\Dialect;

use Neutrino\Database\Schema;
use Neutrino\Support\Fluent;
use Phalcon\Db\Column;

/**
 * Trait PostgresqlTrait
 *
 * @package Neutrino\Database\Schema\Dialect
 */
trait PostgresqlTrait
{

    use Schema\DialectTrait {
        typeTime as _typeTime;
        typeDateTime as _typeDateTime;
        typeTimestamp as _typeTimestamp;
    }

    /**
     * Get SQL Enable foreign key constraints.
     *
     * @return string
     */
    public function enableForeignKeyConstraints()
    {
        return 'SET CONSTRAINTS ALL IMMEDIATE;';
    }

    /**
     * Get SQL Disable foreign key constraints.
     *
     * @return string
     */
    public function disableForeignKeyConstraints()
    {
        return 'SET CONSTRAINTS ALL DEFERRED;';
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

    protected function compileTimableColumn(Fluent $column, $type, $withTz = false)
    {
        $precision = !empty($column['precision']) ? '(' . $column['precision'] . ')' : '';

        $type .= $precision;

        if ($withTz) {
            $type .= ' WITH TIME ZONE';
        }

        if (!empty($column['default'])) {
            $type .= ' DEFAULT ' . $column['default'] . $precision;

            if ($withTz) {
                $type .= ' WITH TIME ZONE';
            }

            unset($column['default']);
        }

        return $type;
    }

    /**
     * Create the column type definition for a timeTz type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeTime(Fluent $column)
    {
        if (!empty($column['precision'])) {
            return [
                'type'          => $this->compileTimableColumn($column, 'TIME'),
                'typeReference' => Column::TYPE_DATETIME,
            ];
        }

        return $this->_typeTime($column);
    }

    /**
     * Create the column type definition for a dateTimeTz type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeDateTime(Fluent $column)
    {
        if (!empty($column['precision'])) {
            return [
                'type'          => $this->compileTimableColumn($column, 'TIMESTAMP'),
                'typeReference' => Column::TYPE_DATETIME,
            ];
        }

        return $this->_typeDateTime($column);
    }

    /**
     * Create the column type definition for a timestampTz type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeTimestamp(Fluent $column)
    {
        if (!empty($column['precision'])) {
            return [
                'type'          => $this->compileTimableColumn($column, 'TIMESTAMP'),
                'typeReference' => Column::TYPE_TIMESTAMP,
            ];
        }

        return $this->_typeTimestamp($column);
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
        return [
            'type'          => $this->compileTimableColumn($column, 'TIME', true),
            'typeReference' => Column::TYPE_DATETIME,
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
        return [
            'type'          => $this->compileTimableColumn($column, 'TIMESTAMP', true),
            'typeReference' => Column::TYPE_DATETIME,
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
        return [
            'type'          => $this->compileTimableColumn($column, 'TIMESTAMP', true),
            'typeReference' => Column::TYPE_TIMESTAMP,
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
            'type'          => 'uuid',
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
            'type'          => 'inet',
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
            'type'          => 'macaddr',
            'typeReference' => Column::TYPE_VARCHAR
        ];
    }

    /**
     * Generates SQL for rename table
     *
     * @param string      $from
     * @param string      $to
     * @param null|string $schema
     *
     * @return string
     */
    public function renameTable($from, $to, $schema = null)
    {
        return "ALTER TABLE " . $this->prepareTable($from, $schema) . " RENAME TO " . $this->prepareTable($to, $schema);
    }
}

<?php

namespace Neutrino\Database\Schema\Dialect;

use Neutrino\Database\Schema;
use Neutrino\Support\Fluent;
use Phalcon\Db\Column;

/**
 * Trait MysqlTrait
 *
 * @package Neutrino\Database\Schema\Dialect
 */
trait MysqlTrait
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
        return 'SET FOREIGN_KEY_CHECKS=1;';
    }

    /**
     * Get SQL Disable foreign key constraints.
     *
     * @return string
     */
    public function disableForeignKeyConstraints()
    {
        return 'SET FOREIGN_KEY_CHECKS=0;';
    }

    /**
     * @param \Neutrino\Support\Fluent $column
     * @param string                   $type
     *
     * @return string
     */
    protected function compileTimableColumn(Fluent $column, $type)
    {
        $precision = !empty($column['precision']) ? '(' . $column['precision'] . ')' : '';

        $type .= $precision;

        if (!empty($column['default'])) {
            $type .= ' DEFAULT ' . $column['default'] . $precision;

            unset($column['default']);
        }

        if (!empty($column['onUpdate'])) {
            $type .= ' ON UPDATE ' . $column['onUpdate'] . $precision;

            unset($column['onUpdate']);
        }

        return $type;
    }

    /**
     * Create the column type definition for a dateTimeTz type.
     *
     * @param \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function typeTime(Fluent $column)
    {
        if (!empty($column['precision'])) {
            if (isset($column['onUpdate'])) {
                unset($column['onUpdate']);
            }

            if (isset($column['default']) && $column['default'] === 'CURRENT_TIMESTAMP') {
                unset($column['default']);
            }

            return [
                'type' => $this->compileTimableColumn($column, 'TIME'),
                'typeReference' => Column::TYPE_DATETIME
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
        if (!empty($column['precision']) || !empty($column['onUpdate'])) {
            return [
                'type' => $this->compileTimableColumn($column, 'DATETIME'),
                'typeReference' => Column::TYPE_DATETIME
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
        if (!empty($column['precision']) || !empty($column['onUpdate'])) {
            return [
                'type' => $this->compileTimableColumn($column, 'TIMESTAMP'),
                'typeReference' => Column::TYPE_TIMESTAMP
            ];
        }

        return $this->_typeTimestamp($column);
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
        return "RENAME TABLE " . $this->prepareTable($from, $schema) . " TO " . $this->prepareTable($to, $schema);
    }
}

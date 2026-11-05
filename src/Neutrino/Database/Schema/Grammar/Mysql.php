<?php

declare(strict_types=1);

namespace Neutrino\Database\Schema\Grammar;

use Neutrino\Database\Schema\Definition;
use Phalcon\Db\Column;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\Dialect;
use Phalcon\Db\DialectInterface;

/**
 * MySQL 8 and MariaDB 10.5+ (`RENAME COLUMN`).
 */
class Mysql extends BaseGrammar
{
    public function enableForeignKeyConstraints(bool $inTransaction = false): string
    {
        return 'SET FOREIGN_KEY_CHECKS=1';
    }

    public function disableForeignKeyConstraints(bool $inTransaction = false): string
    {
        return 'SET FOREIGN_KEY_CHECKS=0';
    }

    /**
     * In one statement: MySQL refuses an `AUTO_INCREMENT` column that is not a key.
     */
    public function addColumn(string $table, ColumnInterface $column, string $schema = ''): string
    {
        $sql = $this->dialect()->addColumn($table, $schema, $column);

        return $column->isPrimary() ? $sql . ', ADD PRIMARY KEY (' . $this->wrap($column->getName()) . ')' : $sql;
    }

    public function renameTable(string $from, string $to, string $schema = ''): string
    {
        return 'RENAME TABLE ' . $this->wrapTable($from, $schema) . ' TO ' . $this->wrapTable($to, $schema);
    }

    /**
     * MySQL commits the transaction before and after each DDL statement.
     */
    public function supportsSchemaTransactions(): bool
    {
        return false;
    }

    protected function wrap(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    protected function dialect(): DialectInterface
    {
        return new Dialect\Mysql();
    }

    protected function typeJsonb(Definition $column): array
    {
        return ['type' => Column::TYPE_JSON];
    }

    protected function typeEnum(Definition $column): array
    {
        $values = array_map(static fn(mixed $value): string => "'" . str_replace("'", "''", self::string($value)) . "'", (array) $column->get('values', []));

        return ['type' => Column::TYPE_ENUM, 'size' => implode(',', $values)];
    }

    protected function typeDateTime(Definition $column): array
    {
        return $this->autoUpdated('DATETIME', Column::TYPE_DATETIME, $column);
    }

    protected function typeTimestamp(Definition $column): array
    {
        return $this->autoUpdated('TIMESTAMP', Column::TYPE_TIMESTAMP, $column);
    }

    /**
     * A `DATETIME` or `TIMESTAMP` column. The Phalcon dialect writes neither `ON UPDATE`, nor the precision of
     * `CURRENT_TIMESTAMP` (which must match the column's).
     *
     * @return array<string, mixed>
     */
    private function autoUpdated(string $sql, int $type, Definition $column): array
    {
        $precision = $this->precision($column);
        $suffix = $precision > 0 ? "($precision)" : '';
        $default = $column->get('default');
        $onUpdate = $column->get('onUpdate');

        if (!is_string($onUpdate) || $onUpdate === '') {
            $definition = $this->temporal($type, $column);

            if ($suffix !== '' && is_string($default) && strtoupper($default) === 'CURRENT_TIMESTAMP') {
                $definition['default'] = $default . $suffix;
            }

            return $definition;
        }

        $sql .= $suffix;
        if (is_string($default) && $default !== '') {
            $sql .= ' DEFAULT ' . (strtoupper($default) === 'CURRENT_TIMESTAMP' ? $default . $suffix : "'" . str_replace("'", "''", $default) . "'");
        }
        $sql .= ' ON UPDATE ' . (strtoupper($onUpdate) === 'CURRENT_TIMESTAMP' ? $onUpdate . $suffix : $onUpdate);

        return ['type' => $sql, 'typeReference' => Column::TYPE_DATE, 'default' => null];
    }
}

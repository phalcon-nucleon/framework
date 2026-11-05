<?php

declare(strict_types=1);

namespace Neutrino\Database\Schema\Grammar;

use Neutrino\Database\Schema\Definition;
use Phalcon\Db\Column;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\Dialect;
use Phalcon\Db\DialectInterface;

/**
 * PostgreSQL. The primary key keeps the name PostgreSQL gives it (`<table>_pkey`).
 */
class Postgresql extends BaseGrammar
{
    public function enableForeignKeyConstraints(bool $inTransaction = false): string
    {
        return 'SET CONSTRAINTS ALL IMMEDIATE';
    }

    public function disableForeignKeyConstraints(bool $inTransaction = false): string
    {
        return 'SET CONSTRAINTS ALL DEFERRED';
    }

    /**
     * The Phalcon dialect changes the type only when the type differs, not its size or scale; and writes the
     * pseudo-types `SERIAL` / `BIGSERIAL` of an auto-increment, which `ALTER COLUMN … TYPE` refuses.
     */
    public function modifyColumn(string $table, ColumnInterface $column, ColumnInterface $current, string $schema = ''): ?string
    {
        $dialect = $this->dialect();
        $sql = rtrim($dialect->modifyColumn($table, $schema, $column, $current), '; ');

        if ($column->getType() === $current->getType() && ($column->getSize() != $current->getSize() || $column->getScale() != $current->getScale())) {
            $sql .= ($sql === '' ? '' : '; ') . 'ALTER TABLE ' . $this->wrapTable($table, $schema) . ' ALTER COLUMN ' . $this->wrap($column->getName())
                . ' TYPE ' . $dialect->getColumnDefinition($column);
        }

        return (string) preg_replace_callback(
            '/\bTYPE (SMALL|BIG)?SERIAL\b/',
            static fn(array $match): string => 'TYPE ' . match ($match[1] ?? '') {
                'SMALL' => 'SMALLINT',
                'BIG'   => 'BIGINT',
                default => 'INTEGER',
            },
            $sql,
        );
    }

    public function addPrimary(string $table, array $columns, string $schema = ''): string
    {
        return 'ALTER TABLE ' . $this->wrapTable($table, $schema) . ' ADD CONSTRAINT ' . $this->wrap($table . '_pkey') . ' PRIMARY KEY (' . $this->columnize($columns) . ')';
    }

    public function dropPrimary(string $table, string $schema = ''): string
    {
        return 'ALTER TABLE ' . $this->wrapTable($table, $schema) . ' DROP CONSTRAINT ' . $this->wrap($table . '_pkey');
    }

    public function dropTables(array $tables, string $schema = ''): array
    {
        return $tables === [] ? [] : ['DROP TABLE IF EXISTS ' . implode(', ', array_map(fn(string $table): string => $this->wrapTable($table, $schema), $tables)) . ' CASCADE'];
    }

    public function primaryAsIndex(): bool
    {
        return false;
    }

    protected function wrap(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    protected function dialect(): DialectInterface
    {
        return new Dialect\Postgresql();
    }

    protected function defaultValue(mixed $value): mixed
    {
        return $value;
    }

    protected function typeTinyInteger(Definition $column): array
    {
        return $this->typeSmallInteger($column);
    }

    protected function typeSmallInteger(Definition $column): array
    {
        // Phalcon has no SMALLSERIAL, and refuses an auto-increment on a type it does not know.
        return $column->get('autoIncrement')
            ? ['type' => 'SMALLSERIAL', 'typeReference' => Column::TYPE_TEXT, 'autoIncrement' => false]
            : ['type' => Column::TYPE_SMALLINTEGER];
    }

    protected function typeMediumInteger(Definition $column): array
    {
        return ['type' => Column::TYPE_INTEGER];
    }

    protected function typeDouble(Definition $column): array
    {
        return ['type' => 'DOUBLE PRECISION', 'typeReference' => Column::TYPE_TEXT];
    }

    protected function typeMediumText(Definition $column): array
    {
        return ['type' => Column::TYPE_TEXT];
    }

    protected function typeLongText(Definition $column): array
    {
        return ['type' => Column::TYPE_TEXT];
    }

    protected function typeBlob(Definition $column): array
    {
        return ['type' => Column::TYPE_BYTEA];
    }

    protected function typeTinyBlob(Definition $column): array
    {
        return ['type' => Column::TYPE_BYTEA];
    }

    protected function typeMediumBlob(Definition $column): array
    {
        return ['type' => Column::TYPE_BYTEA];
    }

    protected function typeLongBlob(Definition $column): array
    {
        return ['type' => Column::TYPE_BYTEA];
    }

    protected function typeDateTime(Definition $column): array
    {
        return $this->typeTimestamp($column);
    }

    protected function typeDateTimeTz(Definition $column): array
    {
        return $this->typeTimestampTz($column);
    }

    protected function typeTime(Definition $column): array
    {
        return $this->temporalSql('TIME', $column, false);
    }

    protected function typeTimeTz(Definition $column): array
    {
        return $this->temporalSql('TIME', $column, true);
    }

    protected function typeTimestamp(Definition $column): array
    {
        return $this->precision($column) > 0 ? $this->temporalSql('TIMESTAMP', $column, false) : ['type' => Column::TYPE_TIMESTAMP];
    }

    protected function typeTimestampTz(Definition $column): array
    {
        return $this->temporalSql('TIMESTAMP', $column, true);
    }

    protected function typeUuid(Definition $column): array
    {
        return ['type' => Column::TYPE_UUID];
    }

    protected function typeIpAddress(Definition $column): array
    {
        return ['type' => Column::TYPE_INET];
    }

    protected function typeMacAddress(Definition $column): array
    {
        return ['type' => Column::TYPE_MACADDR];
    }

    /**
     * The Phalcon dialect writes neither the precision, nor the time zone, nor `TIME`.
     *
     * @return array<string, mixed>
     */
    private function temporalSql(string $type, Definition $column, bool $withTimeZone): array
    {
        $precision = $this->precision($column);

        return [
            'type'          => $type . ($precision > 0 ? "($precision)" : '') . ($withTimeZone ? ' WITH TIME ZONE' : ''),
            'typeReference' => Column::TYPE_DATE,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Neutrino\Database\Schema\Grammar;

use Neutrino\Database\Schema\Definition;
use Phalcon\Db\Column;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\Dialect;
use Phalcon\Db\DialectInterface;
use RuntimeException;

/**
 * SQLite 3.35+ (`DROP COLUMN`, `RENAME COLUMN`).
 *
 * SQLite cannot modify a column, nor add or drop a primary key or a foreign key on an existing table.
 */
class Sqlite extends BaseGrammar
{
    /**
     * In a transaction, `foreign_keys` cannot change: the checks are deferred to the commit instead.
     */
    public function enableForeignKeyConstraints(bool $inTransaction = false): string
    {
        return $inTransaction ? 'PRAGMA defer_foreign_keys = OFF' : 'PRAGMA foreign_keys = ON';
    }

    public function disableForeignKeyConstraints(bool $inTransaction = false): string
    {
        return $inTransaction ? 'PRAGMA defer_foreign_keys = ON' : 'PRAGMA foreign_keys = OFF';
    }

    /**
     * The Phalcon dialect writes the primary key in the column (SQLite cannot add one to an existing table).
     */
    public function addColumn(string $table, ColumnInterface $column, string $schema = ''): string
    {
        return $this->dialect()->addColumn($table, $schema, $column);
    }

    public function addPrimary(string $table, array $columns, string $schema = ''): string
    {
        throw new RuntimeException('SQLite cannot add a primary key to an existing table.');
    }

    public function dropPrimary(string $table, string $schema = ''): string
    {
        throw new RuntimeException('SQLite cannot drop the primary key of an existing table.');
    }

    public function isSystemTable(string $table): bool
    {
        return str_starts_with($table, 'sqlite_');
    }

    protected function wrap(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    protected function dialect(): DialectInterface
    {
        return new Dialect\Sqlite();
    }

    /**
     * SQLite only auto-increments an `INTEGER PRIMARY KEY`.
     */
    protected function typeTinyInteger(Definition $column): array
    {
        return ['type' => Column::TYPE_INTEGER];
    }

    protected function typeSmallInteger(Definition $column): array
    {
        return ['type' => Column::TYPE_INTEGER];
    }

    protected function typeMediumInteger(Definition $column): array
    {
        return ['type' => Column::TYPE_INTEGER];
    }

    protected function typeBigInteger(Definition $column): array
    {
        return ['type' => $column->get('autoIncrement') ? Column::TYPE_INTEGER : Column::TYPE_BIGINTEGER];
    }

    /**
     * Phalcon writes `NUMERIC(p,s)`, which its `describeColumns()` cannot read back ("Column type does not
     * support scale parameter").
     */
    protected function typeDecimal(Definition $column): array
    {
        $definition = parent::typeDecimal($column);

        return ['type' => 'DECIMAL(' . self::string($definition['size']) . ',' . self::string($definition['scale']) . ')', 'typeReference' => Column::TYPE_DATE];
    }

    protected function typeJson(Definition $column): array
    {
        return ['type' => Column::TYPE_TEXT];
    }

    protected function typeJsonb(Definition $column): array
    {
        return ['type' => Column::TYPE_TEXT];
    }

    protected function typeMediumText(Definition $column): array
    {
        return ['type' => Column::TYPE_TEXT];
    }

    protected function typeLongText(Definition $column): array
    {
        return ['type' => Column::TYPE_TEXT];
    }

    protected function typeDateTime(Definition $column): array
    {
        return ['type' => Column::TYPE_DATETIME];
    }

    protected function typeTime(Definition $column): array
    {
        return ['type' => 'TIME', 'typeReference' => Column::TYPE_DATE];
    }

    protected function typeTimestamp(Definition $column): array
    {
        return ['type' => Column::TYPE_TIMESTAMP];
    }
}

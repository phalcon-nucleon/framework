<?php

declare(strict_types=1);

namespace Neutrino\Database\Schema;

use Phalcon\Db\ColumnInterface;

/**
 * What a database needs beyond the DDL of the Phalcon adapter: the column types of the {@see Blueprint}, and the
 * statements Phalcon does not generate (or not the same way on every database).
 *
 * The standard DDL (create, alter and drop tables, columns, indexes and foreign keys) goes through the methods
 * of the connection's adapter, which uses its own dialect.
 */
interface Grammar
{
    /**
     * The Phalcon column of a {@see Blueprint} column (type, size, default, nullability, position…).
     */
    public function column(Definition $column): ColumnInterface;

    /**
     * @param bool $inTransaction Whether the connection is in a transaction (SQLite ignores `foreign_keys` there)
     */
    public function enableForeignKeyConstraints(bool $inTransaction = false): string;

    /**
     * @param bool $inTransaction Whether the connection is in a transaction (SQLite ignores `foreign_keys` there)
     */
    public function disableForeignKeyConstraints(bool $inTransaction = false): string;

    public function renameTable(string $from, string $to, string $schema = ''): string;

    public function renameColumn(string $table, string $from, string $to, string $schema = ''): string;

    /**
     * Statements adding a column, with its primary key when the column is primary.
     */
    public function addColumn(string $table, ColumnInterface $column, string $schema = ''): string;

    /**
     * Statements modifying a column, `null` when those of the adapter's dialect are right.
     */
    public function modifyColumn(string $table, ColumnInterface $column, ColumnInterface $current, string $schema = ''): ?string;

    /**
     * @param list<string> $columns
     */
    public function addPrimary(string $table, array $columns, string $schema = ''): string;

    public function dropPrimary(string $table, string $schema = ''): string;

    /**
     * Statements dropping these tables, whatever their foreign keys.
     *
     * @param list<string> $tables
     *
     * @return list<string>
     */
    public function dropTables(array $tables, string $schema = ''): array;

    /**
     * Tables of the database that are not the application's (`sqlite_sequence`…).
     */
    public function isSystemTable(string $table): bool;

    /**
     * Whether a composite primary key is created with the table as an index (`PRIMARY KEY (a, b)` named `PRIMARY`),
     * or by marking each column as primary.
     */
    public function primaryAsIndex(): bool;

    /**
     * Whether schema changes can be rolled back (PostgreSQL, SQLite). MySQL commits each DDL statement.
     */
    public function supportsSchemaTransactions(): bool;
}

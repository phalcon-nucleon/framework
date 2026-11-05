<?php

declare(strict_types=1);

namespace Neutrino\Database\Schema;

use Closure;
use Neutrino\Support\Db;
use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Db\ColumnInterface;
use RuntimeException;

/**
 * Schema of a connection: tables are created, modified and dropped with a {@see Blueprint}.
 *
 * ```php
 * $schema->create('users', function (Blueprint $table) {
 *     $table->increments('id');
 *     $table->string('email')->unique();
 *     $table->timestamps();
 * });
 * ```
 */
final class Builder
{
    /**
     * Grammars by Phalcon dialect type (`AdapterInterface::getDialectType()`).
     *
     * @var array<string, class-string<Grammar>>
     */
    public const array GRAMMARS = [
        'mysql'      => Grammar\Mysql::class,
        'postgresql' => Grammar\Postgresql::class,
        'sqlite'     => Grammar\Sqlite::class,
    ];

    /**
     * The default string length for migrations.
     */
    public static int $defaultStringLength = 255;

    private readonly AdapterInterface $connection;

    private readonly Grammar $grammar;

    /** @var (Closure(string): Blueprint)|null */
    private ?Closure $resolver = null;

    /**
     * @param AdapterInterface|null $connection The default connection (`db`) by default
     * @param Grammar|null          $grammar    The grammar of the connection's dialect by default
     */
    public function __construct(?AdapterInterface $connection = null, ?Grammar $grammar = null)
    {
        $this->connection = $connection ?? Db::connection();
        $this->grammar = $grammar ?? self::grammarOf($this->connection);
    }

    /**
     * The grammar of a connection, by the type of its dialect.
     */
    public static function grammarOf(AdapterInterface $connection): Grammar
    {
        $class = self::GRAMMARS[$connection->getDialectType()] ?? throw new RuntimeException(
            'No schema grammar for the "' . $connection->getDialectType() . '" dialect: pass a ' . Grammar::class . ' to the ' . self::class . '.',
        );

        return new $class();
    }

    /**
     * Set the default string length for migrations.
     */
    public static function defaultStringLength(int $length): void
    {
        self::$defaultStringLength = $length;
    }

    public function connection(): AdapterInterface
    {
        return $this->connection;
    }

    public function grammar(): Grammar
    {
        return $this->grammar;
    }

    /**
     * Determine if the given table exists.
     */
    public function hasTable(string $table): bool
    {
        return $this->connection->tableExists($table);
    }

    /**
     * Determine if the given table has a given column.
     */
    public function hasColumn(string $table, string $column): bool
    {
        return $this->hasColumns($table, [$column]);
    }

    /**
     * Determine if the given table has given columns.
     *
     * @param list<string> $columns
     */
    public function hasColumns(string $table, array $columns): bool
    {
        $existing = array_map(static fn(ColumnInterface $column): string => strtolower($column->getName()), $this->getColumnListing($table));

        return array_diff(array_map(strtolower(...), $columns), $existing) === [];
    }

    /**
     * The Phalcon type (`Db\Column::TYPE_*`) of a column, `null` when the column does not exist.
     */
    public function getColumnType(string $table, string $column): int|string|null
    {
        foreach ($this->getColumnListing($table) as $definition) {
            if ($definition->getName() === $column) {
                return $definition->getType();
            }
        }

        return null;
    }

    /**
     * The columns of a table.
     *
     * @return list<ColumnInterface>
     */
    public function getColumnListing(string $table): array
    {
        return array_values($this->connection->describeColumns($table));
    }

    /**
     * Modify a table on the schema.
     *
     * @param Closure(Blueprint): mixed $callback
     */
    public function table(string $table, Closure $callback): void
    {
        $blueprint = $this->createBlueprint($table)->update();
        $callback($blueprint);

        $this->build($blueprint);
    }

    /**
     * Create a new table on the schema.
     *
     * @param Closure(Blueprint): mixed $callback
     */
    public function create(string $table, Closure $callback): void
    {
        $blueprint = $this->createBlueprint($table)->create();
        $callback($blueprint);

        $this->build($blueprint);
    }

    /**
     * Drop a table from the schema.
     */
    public function drop(string $table): void
    {
        $this->build($this->createBlueprint($table)->drop());
    }

    /**
     * Drop a table from the schema if it exists.
     */
    public function dropIfExists(string $table): void
    {
        $this->build($this->createBlueprint($table)->dropIfExists());
    }

    /**
     * Drop all tables from the database.
     */
    public function dropAllTables(): void
    {
        $tables = array_values(array_filter($this->connection->listTables(), fn(string $table): bool => !$this->grammar->isSystemTable($table)));

        if ($tables === []) {
            return;
        }

        $this->withoutForeignKeyConstraints(function () use ($tables): void {
            foreach ($this->grammar->dropTables($tables) as $sql) {
                $this->connection->execute($sql);
            }
        });
    }

    /**
     * Rename a table on the schema.
     */
    public function rename(string $from, string $to): void
    {
        $blueprint = $this->createBlueprint($from)->update();
        $blueprint->rename($to);

        $this->build($blueprint);
    }

    /**
     * Execute a raw SQL statement.
     */
    public function execute(string $sql): void
    {
        $this->connection->execute($sql);
    }

    /**
     * Enable foreign key constraints.
     */
    public function enableForeignKeyConstraints(): bool
    {
        return $this->connection->execute($this->grammar->enableForeignKeyConstraints($this->connection->isUnderTransaction()));
    }

    /**
     * Disable foreign key constraints. On SQLite, in a transaction (a migration), defers them to the commit.
     */
    public function disableForeignKeyConstraints(): bool
    {
        return $this->connection->execute($this->grammar->disableForeignKeyConstraints($this->connection->isUnderTransaction()));
    }

    /**
     * Runs a callback with the foreign key constraints disabled.
     *
     * @template T
     *
     * @param Closure(): T $callback
     *
     * @return T
     */
    public function withoutForeignKeyConstraints(Closure $callback): mixed
    {
        $this->disableForeignKeyConstraints();

        try {
            return $callback();
        } finally {
            $this->enableForeignKeyConstraints();
        }
    }

    /**
     * Set the Blueprint resolver callback.
     *
     * @param Closure(string): Blueprint $resolver
     */
    public function blueprintResolver(Closure $resolver): void
    {
        $this->resolver = $resolver;
    }

    /**
     * Execute the blueprint to build / modify the table.
     */
    private function build(Blueprint $blueprint): void
    {
        $blueprint->build($this->connection, $this->grammar);
    }

    private function createBlueprint(string $table): Blueprint
    {
        return $this->resolver === null ? new Blueprint($table) : ($this->resolver)($table);
    }
}

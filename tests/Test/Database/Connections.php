<?php

declare(strict_types=1);

namespace Test\Database;

use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Db\Adapter\Pdo;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * SQLite in memory, and the MySQL and PostgreSQL servers of the CI (`DB_MYSQL_HOST`, `DB_PGSQL_HOST`): tests
 * on these are skipped without the variable.
 *
 * A test using {@see Connections::connections()} as {@see DataProvider} receives the name of the database.
 */
trait Connections
{
    /** @var array<string, AdapterInterface> */
    private array $opened = [];

    /**
     * @return iterable<string, array{string}>
     */
    public static function connections(): iterable
    {
        yield 'sqlite' => ['sqlite'];
        yield 'mysql' => ['mysql'];
        yield 'postgresql' => ['postgresql'];
    }

    /**
     * The adapter options of a database, `null` when it is not available (no server or no PDO driver).
     *
     * @return array{adapter: string, config: array<string, string>}|null
     */
    protected static function connectionConfig(string $database): ?array
    {
        $host = getenv(match ($database) {
            'mysql'      => 'DB_MYSQL_HOST',
            'postgresql' => 'DB_PGSQL_HOST',
            default      => 'NONE',
        });
        $driver = ['sqlite' => 'sqlite', 'mysql' => 'mysql', 'postgresql' => 'pgsql'][$database] ?? '';

        if (!in_array($driver, \PDO::getAvailableDrivers(), true)) {
            return null;
        }

        return match ($database) {
            'sqlite'     => ['adapter' => 'sqlite', 'config' => ['dbname' => ':memory:']],
            'mysql'      => is_string($host) && $host !== '' ? ['adapter' => 'mysql', 'config' => ['host' => $host, 'username' => 'root', 'password' => 'root', 'dbname' => 'nucleon', 'charset' => 'utf8mb4']] : null,
            'postgresql' => is_string($host) && $host !== '' ? ['adapter' => 'postgresql', 'config' => ['host' => $host, 'username' => 'postgres', 'password' => 'postgres', 'dbname' => 'nucleon']] : null,
            default      => null,
        };
    }

    /**
     * Opens a connection on an empty database, or skips the test.
     */
    protected function open(string $database): AdapterInterface
    {
        $config = self::connectionConfig($database) ?? $this->markTestSkipped("No $database server.");

        $db = match ($database) {
            'mysql'      => new Pdo\Mysql($config['config']),
            'postgresql' => new Pdo\Postgresql($config['config']),
            default      => new Pdo\Sqlite($config['config']),
        };

        self::emptyDatabase($db);

        return $this->opened[$database] = $db;
    }

    protected function closeConnections(): void
    {
        foreach ($this->opened as $db) {
            self::emptyDatabase($db);
        }

        $this->opened = [];
    }

    private static function emptyDatabase(AdapterInterface $db): void
    {
        $tables = array_filter($db->listTables(), static fn(string $table): bool => !str_starts_with($table, 'sqlite_'));

        if ($tables === []) {
            return;
        }

        match ($db->getDialectType()) {
            'mysql'      => $db->execute('SET FOREIGN_KEY_CHECKS=0') && $db->execute('DROP TABLE IF EXISTS `' . implode('`, `', $tables) . '`') && $db->execute('SET FOREIGN_KEY_CHECKS=1'),
            'postgresql' => $db->execute('DROP TABLE IF EXISTS "' . implode('", "', $tables) . '" CASCADE'),
            default      => array_map(static fn(string $table): bool => $db->execute('DROP TABLE IF EXISTS "' . $table . '"'), $tables),
        };
    }
}

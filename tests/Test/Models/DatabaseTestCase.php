<?php

declare(strict_types=1);

namespace Test\Models;

use Neutrino\Config\Config;
use Neutrino\Constants\Services;
use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\Providers;
use Phalcon\Db\Adapter\Pdo\Sqlite;
use Phalcon\Di\Di;
use Phalcon\Di\FactoryDefault;
use Phalcon\Events\Manager;
use PHPUnit\Framework\TestCase;

/**
 * A container with the database and model providers, on SQLite in memory. `$queries` logs the SQL sent.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected FactoryDefault $di;

    /** @var list<string> */
    protected array $queries = [];

    protected function setUp(): void
    {
        $this->di = $this->container();
        $this->createTables();
        $this->queries = [];
    }

    protected function tearDown(): void
    {
        Di::reset();
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function container(array $config = []): FactoryDefault
    {
        $di = new FactoryDefault();
        Di::setDefault($di);
        $di->setShared(Services::CONFIG, new Config(array_replace_recursive([
            'database' => ['default' => 'main', 'connections' => ['main' => ['adapter' => 'sqlite', 'config' => ['dbname' => ':memory:']]]],
        ], $config)));

        ProviderRegistrar::register($di, [Providers\Database::class, Providers\Model::class]);

        return $this->di = $di;
    }

    protected function db(string $service = Services::DB): Sqlite
    {
        /** @var Sqlite $db */
        $db = $this->di->getShared($service);

        if ($db->getEventsManager() === null) {
            $manager = new Manager();
            $manager->attach('db:beforeQuery', function ($event, Sqlite $db): void {
                $this->queries[] = $db->getSQLStatement();
            });
            $db->setEventsManager($manager);
        }

        return $db;
    }

    protected function createTables(string $service = Services::DB): void
    {
        $db = $this->db($service);
        $db->execute('CREATE TABLE articles (id INTEGER PRIMARY KEY, title_col VARCHAR(100) NOT NULL, summary TEXT, views INTEGER NOT NULL DEFAULT 0, rating DECIMAL(3,1), published BOOLEAN NOT NULL DEFAULT 0, created_at VARCHAR(30), updated_at VARCHAR(30), deleted BOOLEAN NOT NULL DEFAULT 0)');
        $db->execute('CREATE TABLE authors (author_id INTEGER PRIMARY KEY, name VARCHAR(100) NOT NULL, mail VARCHAR(100), level INTEGER NOT NULL DEFAULT 1, created_at VARCHAR(30), updated_at VARCHAR(30), removed BOOLEAN NOT NULL DEFAULT 0)');
        $db->execute('CREATE TABLE plain (id INTEGER PRIMARY KEY, name VARCHAR(100))');
    }

    /**
     * @return list<string>
     */
    protected function introspectionQueries(): array
    {
        return array_values(array_filter($this->queries, static fn(string $sql): bool => (bool) preg_match('/PRAGMA|DESCRIBE|information_schema|sqlite_master/i', $sql)));
    }
}

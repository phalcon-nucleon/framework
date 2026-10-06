<?php

declare(strict_types=1);

namespace Test\Database\Migrations;

use Neutrino\Config\Config;
use Neutrino\Constants\Services;
use Neutrino\Database\Migrations\Migrator;
use Neutrino\Database\Migrations\Storage\StorageInterface;
use Neutrino\Database\Providers\MigrationsServicesProvider;
use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\Providers;
use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Di\Di;
use Phalcon\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;
use Test\Database\Connections;

/**
 * A container with the database, model and migration providers: `main` (the default connection) on the
 * database of the test, `reports` on SQLite in memory.
 */
abstract class MigrationsTestCase extends TestCase
{
    use Connections;

    protected const string MIGRATIONS = BASE_PATH . '/migrations';

    protected FactoryDefault $di;

    protected function tearDown(): void
    {
        if (isset($this->di)) {
            foreach ([Services::DB, Services::DB . '.reports'] as $service) {
                $db = $this->di->getShared($service);
                if ($db instanceof AdapterInterface) {
                    $this->opened[$service] = $db;
                }
            }
        }

        $this->closeConnections();
        Di::reset();
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function boot(string $database = 'sqlite', array $config = []): FactoryDefault
    {
        // Empties the database of the test.
        $this->open($database);

        $di = new FactoryDefault();
        Di::setDefault($di);
        $di->setShared(Services::CONFIG, new Config(array_replace_recursive([
            'database'   => ['default' => 'main', 'connections' => [
                'main'    => self::connectionConfig($database),
                'reports' => self::connectionConfig('sqlite'),
            ]],
            'migrations' => ['path' => self::MIGRATIONS . '/schema'],
        ], $config)));

        ProviderRegistrar::register($di, [Providers\Database::class, Providers\Model::class, MigrationsServicesProvider::class]);

        return $this->di = $di;
    }

    protected function db(string $service = Services::DB): AdapterInterface
    {
        /** @var AdapterInterface */
        return $this->di->getShared($service);
    }

    protected function migrator(): Migrator
    {
        /** @var Migrator */
        return $this->di->getShared(Migrator::class);
    }

    protected function storage(): StorageInterface
    {
        /** @var StorageInterface */
        return $this->di->getShared(StorageInterface::class);
    }

    /**
     * Tables of a connection, sorted.
     *
     * @return list<string>
     */
    protected function tables(string $service = Services::DB): array
    {
        $tables = array_values(array_filter($this->db($service)->listTables(), static fn(string $table): bool => !str_starts_with($table, 'sqlite_')));
        sort($tables);

        return $tables;
    }
}

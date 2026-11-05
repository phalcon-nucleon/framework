<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Interfaces\Providable;
use Phalcon\Config\Config;
use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Db\Adapter\Pdo;
use Phalcon\Di\Injectable;
use Phalcon\Di\Service;
use RuntimeException;

/**
 * One `db.<name>` service per connection of `database.connections`, built on first use, and `db`: the
 * `database.default` connection.
 *
 * ```php
 * 'database' => [
 *     'default'     => 'main',
 *     'connections' => [
 *         'main'    => ['adapter' => 'mysql', 'config' => ['host' => '…', 'dbname' => '…', 'username' => '…', 'password' => '…']],
 *         'reports' => ['adapter' => 'postgresql', 'config' => [...]],
 *     ],
 * ],
 * ```
 *
 * `adapter`: `mysql`, `postgresql`, `sqlite`, or a class implementing {@see AdapterInterface} built with
 * `new $class(array $config)`. A model uses another connection with `setConnectionService('db.reports')`
 * (or `setReadConnectionService()` / `setWriteConnectionService()`).
 */
class Database extends Injectable implements Providable
{
    /**
     * @var array<string, class-string<AdapterInterface>>
     */
    public const array ADAPTERS = [
        'mysql'      => Pdo\Mysql::class,
        'postgresql' => Pdo\Postgresql::class,
        'sqlite'     => Pdo\Sqlite::class,
    ];

    public function registering(): void
    {
        $di = $this->getDI();

        /** @var Config $config */
        $config = $di->getShared(Services::CONFIG);
        $connections = $config->path('database.connections');
        $default = $config->path('database.default');

        if (!$connections instanceof Config) {
            throw new RuntimeException('Database: no connection, set "database.connections".');
        }

        foreach ($connections as $name => $connection) {
            $name = (string) $name;
            $service = new Service(function () use ($name, $connection): AdapterInterface {
                return Database::connect($name, $connection instanceof Config ? $connection->toArray() : []);
            }, true);

            $di->setService(Services::DB . '.' . $name, $service);

            if ($name === $default || ($default === null && !$di->has(Services::DB))) {
                $di->setService(Services::DB, $service);
            }
        }

        if (!$di->has(Services::DB)) {
            throw new RuntimeException('Database: the default connection "' . (is_scalar($default) ? $default : '') . '" is not in "database.connections".');
        }
    }

    /**
     * Opens a connection.
     *
     * @param array<mixed> $connection `['adapter' => …, 'config' => […]]`
     */
    public static function connect(string $name, array $connection): AdapterInterface
    {
        $adapter = $connection['adapter'] ?? null;
        $options = $connection['config'] ?? $connection['options'] ?? [];

        if (!is_array($options)) {
            throw new RuntimeException("Database connection \"$name\": \"config\" must be an array.");
        }

        $class = is_string($adapter) ? self::ADAPTERS[strtolower($adapter)] ?? $adapter : null;

        if ($class === null || !class_exists($class) || !is_subclass_of($class, AdapterInterface::class)) {
            throw new RuntimeException("Database connection \"$name\": unknown adapter, use mysql, postgresql, sqlite or a class implementing " . AdapterInterface::class . '.');
        }

        return new $class($options);
    }
}

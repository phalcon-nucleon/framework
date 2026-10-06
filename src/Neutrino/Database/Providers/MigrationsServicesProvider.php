<?php

declare(strict_types=1);

namespace Neutrino\Database\Providers;

use Neutrino\Cli\ProvidesTasks;
use Neutrino\Constants\Services;
use Neutrino\Database\Cli\Tasks;
use Neutrino\Database\Migrations\MigrationCreator;
use Neutrino\Database\Migrations\Migrator;
use Neutrino\Database\Migrations\Prefix\PrefixInterface;
use Neutrino\Database\Migrations\Prefix\TimestampPrefix;
use Neutrino\Database\Migrations\Storage\DatabaseStorage;
use Neutrino\Database\Migrations\Storage\StorageInterface;
use Neutrino\Interfaces\Providable;
use Phalcon\Config\Config;
use Phalcon\Di\DiInterface;
use Phalcon\Di\Injectable;
use RuntimeException;

/**
 * Migration services and the `migrate*` and `make:migration` commands, declared in the providers of the CLI kernel.
 *
 * ```php
 * 'migrations' => [
 *     'path'       => BASE_PATH . '/migrations',
 *     'prefix'     => TimestampPrefix::class, // or DatePrefix::class
 *     'storage'    => DatabaseStorage::class,
 *     'connection' => null,                   // connection of the `migrations` table (the default one)
 * ],
 * ```
 */
class MigrationsServicesProvider extends Injectable implements Providable, ProvidesTasks
{
    public static function tasks(): array
    {
        return [
            'migrate'               => Tasks\MigrateTask::class,
            'migrate:install'       => Tasks\InstallTask::class,
            'migrate:status'        => Tasks\StatusTask::class,
            'migrate:rollback'      => Tasks\RollbackTask::class,
            'migrate:reset'         => Tasks\ResetTask::class,
            'migrate:refresh'       => Tasks\RefreshTask::class,
            'migrate:fresh'         => Tasks\FreshTask::class,
            'make:migration {name}' => Tasks\MakerTask::class,
        ];
    }

    public function registering(): void
    {
        $di = $this->getDI();

        $di->setShared(PrefixInterface::class, function () use ($di): PrefixInterface {
            return MigrationsServicesProvider::make($di, 'prefix', TimestampPrefix::class, PrefixInterface::class);
        });

        $di->setShared(StorageInterface::class, function () use ($di): StorageInterface {
            return MigrationsServicesProvider::make($di, 'storage', DatabaseStorage::class, StorageInterface::class);
        });

        $di->setShared(Migrator::class, function () use ($di): Migrator {
            /** @var StorageInterface $storage */
            $storage = $di->getShared(StorageInterface::class);
            /** @var PrefixInterface $prefix */
            $prefix = $di->getShared(PrefixInterface::class);

            return new Migrator($storage, $prefix);
        });

        $di->setShared(MigrationCreator::class, function () use ($di): MigrationCreator {
            /** @var PrefixInterface $prefix */
            $prefix = $di->getShared(PrefixInterface::class);

            return new MigrationCreator($prefix);
        });
    }

    /**
     * Builds the class of `migrations.<key>`.
     *
     * @template T of object
     *
     * @param class-string<T> $default
     * @param class-string<T> $interface
     *
     * @return T
     */
    public static function make(DiInterface $di, string $key, string $default, string $interface): object
    {
        $config = $di->getShared(Services::CONFIG);
        $class = $config instanceof Config ? $config->path("migrations.$key") : null;
        $class = is_string($class) && $class !== '' ? $class : $default;

        if (!class_exists($class) || !is_subclass_of($class, $interface)) {
            throw new RuntimeException(str_ends_with($class, 'FileStorage')
                ? 'The migrations FileStorage was removed in Nucleon 2.0: use ' . DatabaseStorage::class . ' (see UPGRADING-2.0.md).'
                : "migrations.$key: $class is not a class implementing $interface.");
        }

        /** @var T */
        return is_a($class, DatabaseStorage::class, true) ? new $class($di) : new $class();
    }
}

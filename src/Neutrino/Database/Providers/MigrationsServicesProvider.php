<?php

namespace Neutrino\Database\Providers;

use Neutrino\Constants\Services;
use Neutrino\Database\Migrations\MigrationCreator;
use Neutrino\Database\Migrations\Migrator;
use Neutrino\Database\Migrations\Prefix\PrefixInterface;
use Neutrino\Database\Migrations\Storage\StorageInterface;
use Neutrino\Interfaces\Providable;
use Phalcon\Di;
use Phalcon\DiInterface;

/**
 * Class MigrationProvider
 *
 * @package Neutrino\Database\Providers
 */
class MigrationsServicesProvider implements Providable
{
    /**
     * Called upon bootstrap the application.
     * Adds to container services desired services.
     *
     * @return void
     */
    public function registering()
    {
        $di = Di::getDefault();

        $this->registerPrefix($di);
        $this->registerStorage($di);
        $this->registerMigrator($di);
        $this->registerMigrationCreator($di);
    }

    /**
     * Register Migrations\Prefix
     *
     * @param \Phalcon\DiInterface $di
     */
    protected function registerPrefix(DiInterface $di)
    {
        $di->set(PrefixInterface::class, function () {
            $config = $this->get(Services::CONFIG);

            return new $config->migrations->prefix;
        });
    }

    /**
     * Register Migrations\Storage
     *
     * @param \Phalcon\DiInterface $di
     */
    protected function registerStorage(DiInterface $di)
    {
        $di->set(StorageInterface::class, function () {
            $config = $this->get(Services::CONFIG);

            return new $config->migrations->storage;
        });
    }

    /**
     * Register Migrations\Migrator
     *
     * @param \Phalcon\DiInterface $di
     */
    protected function registerMigrator(DiInterface $di)
    {
        $di->set(Migrator::class, [
            'className' => Migrator::class,
            'arguments' => [
                [
                    'type' => 'service',
                    'name' => StorageInterface::class,
                ]
            ]
        ], true);
    }

    /**
     * Register Migrations\MigrationCreator
     *
     * @param \Phalcon\DiInterface $di
     */
    protected function registerMigrationCreator(DiInterface $di)
    {
        $di->set(MigrationCreator::class, [
            'className' => MigrationCreator::class,
            'arguments' => [
                [
                    'type' => 'service',
                    'name' => PrefixInterface::class,
                ]
            ]
        ], true);
    }
}

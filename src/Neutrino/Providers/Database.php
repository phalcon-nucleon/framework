<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Database\DatabaseStrategy;
use Neutrino\Interfaces\Providable;
use Phalcon\Di\Injectable;
use Phalcon\Di\Service;


/**
 * Class Database
 *
 * @package Neutrino\Foundation\Bootstrap
 */
class Database extends Injectable implements Providable
{
    /**
     * Database connection is created based in the parameters defined in the configuration file
     */
    public function registering()
    {
        $di = $this->getDI();

        $database = (array)$this->{Services::CONFIG}->database;
        $connections = (array)$database['connections'];

        if (count($connections) > 1) {
            $di->setShared(Services::DB, DatabaseStrategy::class);

            foreach ((array)$this->{Services::CONFIG}->database->connections as $name => $connection) {
                $di->setShared(Services::DB . '.' . $name, function () use ($connection) {
                    $connection = (array)$connection;

                    $adapter = $connection['adapter'];
                    unset($connection['adapter']);

                    return new $adapter($connection);
                });
            }
        } else {
            $connection = array_shift($connections);

            $service = new Service(Services::DB . '.' . $database['default'], function () use ($connection) {
                $connection = (array)$connection;

                $adapter = $connection['adapter'];
                unset($connection['adapter']);

                return new $adapter($connection);
            }, true);

            $di->setRaw(Services::DB . '.' . $database['default'], $service);
            $di->setRaw(Services::DB, $service);
        }
    }
}

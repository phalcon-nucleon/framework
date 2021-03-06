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

        $database = (array)$di->getShared(Services::CONFIG)->database;
        $connections = (array)$database['connections'];

        if (count($connections) > 1) {
            $di->setShared(Services::DB, DatabaseStrategy::class);

            foreach ($connections as $name => $connection) {
                $di->setShared(Services::DB . '.' . $name, function () use ($connection) {
                    return new $connection['adapter']((array)$connection['config']);
                });
            }
        } else {
            $connection = array_shift($connections);
            $serviceName = Services::DB . '.' . $database['default'];

            $service = new Service($serviceName, function () use ($connection) {
                return new $connection['adapter']((array)$connection['config']);
            }, true);

            $di->setRaw($serviceName, $service);
            $di->setRaw(Services::DB, $service);
        }
    }
}

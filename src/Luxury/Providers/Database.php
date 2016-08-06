<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Database
 *
 * @package Luxury\Foundation\Bootstrap
 */
class Database implements Providable
{
    /**
     * Database connection is created based in the parameters defined in the configuration file
     *
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::DB, function () {
            /* @var \Phalcon\Di $this */
            $dbConfig = $this->getShared(Services::CONFIG)->database->toArray();
            $adapter  = $dbConfig['adapter'];
            unset($dbConfig['adapter']);

            $class = 'Phalcon\Db\Adapter\Pdo\\' . $adapter;

            return new $class($dbConfig);
        });
    }
}

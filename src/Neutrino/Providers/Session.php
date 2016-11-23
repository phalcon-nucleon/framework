<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Phalcon\Di\Injectable;
use Phalcon\Session\Adapter\Files as FilesAdapter;
use Phalcon\Session\Adapter\Libmemcached as LibmemcachedAdapter;
use Phalcon\Session\Adapter\Memcache as MemcacheAdapter;
use Phalcon\Session\Adapter\Redis as RedisAdapter;
use Neutrino\Interfaces\Providable;
use Phalcon\Session\Bag;

/**
 * Class Session
 *
 *  @package Neutrino\Foundation\Bootstrap
 */
class Session extends Injectable implements Providable
{
    /**
     * Start the session the first time some component request the session service
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    public function registering()
    {
        $di = $this->getDI();

        $di->set(Services::SESSION_BAG, Bag::class);

        $di->setShared(Services::SESSION, function () {
            /** @var \Phalcon\DiInterface $this */

            $adapter = $this->getShared(Services::CONFIG)->session->adapter;

            switch ($adapter){
                case 'Aerospike':
                case 'Database':
                case 'HandlerSocket':
                case 'Mongo':
                case 'Files':
                case 'Libmemcached':
                case 'Memcache':
                case 'Redis':
                    $class = 'Phalcon\Session\Adapter\\' . $adapter;
                    break;
                case FilesAdapter::class:
                case LibmemcachedAdapter::class:
                case MemcacheAdapter::class:
                case RedisAdapter::class:
                    $class = $adapter;
                    break;
                default:
                    $class = $adapter;

                    if(!class_exists($adapter)){
                        throw new \RuntimeException("Session Adapter $class not found.");
                    }
            }

            try {
                /** @var \Phalcon\Session\Adapter|\Phalcon\Session\AdapterInterface $session */
                $session = new $class();
            } catch (\Throwable $e) {
                throw new \RuntimeException("Session Adapter $class construction fail.", $e);
            }

            $session->start();

            return $session;
        });
    }
}

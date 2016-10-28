<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Exceptions\SessionAdapterNotFound;
use Phalcon\Session\Adapter\Aerospike;
use Phalcon\Session\Adapter\Database;
use Phalcon\Session\Adapter\Files;
use Phalcon\Session\Adapter\HandlerSocket;
use Phalcon\Session\Adapter\Libmemcached;
use Phalcon\Session\Adapter\Memcache;
use Phalcon\Session\Adapter\Mongo;
use Phalcon\Session\Adapter\Redis;
use Phalcon\Session\Bag;

/**
 * Class Session
 *
 * @package Luxury\Foundation\Bootstrap
 */
class Session extends Provider
{
    protected $name = Services::SESSION;

    protected $shared = true;

    /**
     * Start the session the first time some component request the session service
     *
     * @throws \Luxury\Exceptions\SessionAdapterNotFound
     *
     * @return void
     */
    public function registering()
    {
        $di = $this->getDI();

        $di->set(Services::SESSION_BAG, Bag::class);
        $di->setShared($this->name, function () {
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
                case Aerospike::class:
                case Database::class:
                case HandlerSocket::class:
                case Mongo::class:
                case Files::class:
                case Libmemcached::class:
                case Memcache::class:
                case Redis::class:
                    $class = $adapter;
                    break;
                default:
                    $class = $adapter;

                    if(!class_exists($adapter)){
                        throw new SessionAdapterNotFound($adapter);
                    }
            }

            try {
                /** @var \Phalcon\Session\Adapter|\Phalcon\Session\AdapterInterface $session */
                $session = new $class();
            } catch (\Error $e) {
                throw new SessionAdapterNotFound($class, $e);
            } catch (\Exception $e) {
                throw new SessionAdapterNotFound($class, $e);
            }

            $session->start();

            return $session;
        });
    }

    /**
     * @return mixed
     */
    protected function register()
    {
        return;
    }
}

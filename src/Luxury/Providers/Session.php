<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Exceptions\SessionAdapterNotFound;
use Phalcon\Session\Adapter\Aerospike as AerospikeAdapter;
use Phalcon\Session\Adapter\Database as DatabaseAdapter;
use Phalcon\Session\Adapter\Files as FilesAdapter;
use Phalcon\Session\Adapter\HandlerSocket as HandlerSocketAdapter;
use Phalcon\Session\Adapter\Libmemcached as LibmemcachedAdapter;
use Phalcon\Session\Adapter\Memcache as MemcacheAdapter;
use Phalcon\Session\Adapter\Mongo as MongoAdapter;
use Phalcon\Session\Adapter\Redis as RedisAdapter;
use Luxury\Interfaces\Providable;
use Luxury\Support\Traits\InjectionAwareTrait;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Session\Bag;

/**
 * Class Session
 *
 * @package Luxury\Foundation\Bootstrap
 */
class Session implements Providable, InjectionAwareInterface
{
    use InjectionAwareTrait;

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
                case AerospikeAdapter::class:
                case DatabaseAdapter::class:
                case HandlerSocketAdapter::class:
                case MongoAdapter::class:
                case FilesAdapter::class:
                case LibmemcachedAdapter::class:
                case MemcacheAdapter::class:
                case RedisAdapter::class:
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
}

<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\Cache\Frontend\None as CacheAdapter;
use Phalcon\DiInterface;

/**
 * Class Cache
 *
 * @package     Luxury\Providers
 */
class Cache implements Providable
{

    /**
     * @param \Phalcon\DiInterface $di
     *
     * @return void
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::CACHE, function () {
            /** @var \Phalcon\Di $this */
            $cache = $this->getShared(Services::CONFIG)->cache;

            switch ($driverName = $cache->driver) {
                case 'Aerospike':
                case 'Apc':
                case 'Database':
                case 'Libmemcached':
                case 'File':
                case 'Memcache':
                case 'Memory':
                case 'Mongo':
                case 'Redis':
                case 'Wincache':
                case 'Xcache':
                    break;
                default:
                    throw new \RuntimeException;
            }

            $driverClass = "\\Phalcon\\Cache\\Backend\\$driverName";

            return new $driverClass(new CacheAdapter(), $cache->options);
        });
    }
}

<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
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

            // Acceptable Driver (Backend)
            $driver = $cache->driver;
            if (empty($driver)) {
                $driver = $cache->backend;
            }
            switch ($driver = ucfirst($driver)) {
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

            // Acceptable Adapter (Frontend)
            $adapter = $cache->adapter;
            if (empty($driver)) {
                $adapter = $cache->frontend;
            }
            switch ($adapter = ucfirst($adapter)) {
                case 'Data':
                case 'Json':
                case 'File':
                case 'Base64':
                case 'Output':
                case 'Igbinary':
                case 'None':
                    break;
                case null:
                    $adapter = 'None';
                    break;
                default:
                    throw new \RuntimeException;
            }

            $adapterClass = "\\Phalcon\\Cache\\Frontend\\$adapter";
            $driverClass  = "\\Phalcon\\Cache\\Backend\\$driver";

            $options = isset($cache->options) ? (array)$cache->options : [];

            return new $driverClass(new $adapterClass($options), $options);
        });
    }
}

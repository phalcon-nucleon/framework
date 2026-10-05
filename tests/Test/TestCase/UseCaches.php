<?php

declare(strict_types=1);

namespace Test\TestCase;

use Neutrino\Support\Facades\Cache;
use Test\Cache\StubBackend;

/**
 * Cache stores of the cache tests. The stores are ported with the cache (E7).
 */
trait UseCaches
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::setConfig([
            'cache' => [
                'default' => 'memory',
                'stores' => [
                    'memory' => [
                        'driver' => \Phalcon\Cache\Backend\Memory::class,
                        'adapter' => 'None',
                    ],
                    'file'   => [
                        'adapter' => 'Data', // Files, Memcache, Libmemcached, Redis
                        'driver'  => 'File', // Files, Memcache, Libmemcached, Redis
                        'options' => ['cacheDir' => static::$cache_dir],
                    ],
                    'fast'    => [
                        'adapter' => 'Json', // Files, Memcache, Libmemcached, Redis
                        'driver'  => 'File', // Files, Memcache, Libmemcached, Redis
                        'options' => ['cacheDir' => static::$cache_dir],
                    ],
                    'slow'    => [
                        'adapter' => 'Base64', // Files, Memcache, Libmemcached, Redis
                        'driver'  => 'File', // Files, Memcache, Libmemcached, Redis
                        'options' => ['cacheDir' => static::$cache_dir],
                    ],
                    'output'  => [
                        'adapter' => 'Output', // Files, Memcache, Libmemcached, Redis
                        'driver'  => 'File', // Files, Memcache, Libmemcached, Redis
                        'options' => ['cacheDir' => static::$cache_dir],
                    ],
                    'stub'    => [
                        'adapter' => 'Data', // Files, Memcache, Libmemcached, Redis
                        'driver'  => StubBackend::class, // Files, Memcache, Libmemcached, Redis
                        'options' => ['cacheDir' => static::$cache_dir],
                    ],
                ],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Cache::uses('file');

        parent::tearDown();
    }
}

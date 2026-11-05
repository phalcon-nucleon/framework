<?php

declare(strict_types=1);

namespace Test\TestCase;

use Test\Cache\StubAdapter;

/**
 * Cache stores of the cache tests.
 */
trait UseCaches
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::setConfig([
            'cache' => [
                'default' => 'memory',
                'stores'  => [
                    'memory' => ['adapter' => 'memory'],
                    'file'   => ['adapter' => 'stream', 'options' => ['storageDir' => static::$cache_dir]],
                    'fast'   => ['adapter' => 'stream', 'serializer' => 'json', 'options' => ['storageDir' => static::$cache_dir]],
                    'slow'   => ['adapter' => 'stream', 'serializer' => 'base64', 'options' => ['storageDir' => static::$cache_dir]],
                    'stub'   => ['adapter' => StubAdapter::class, 'options' => ['prefix' => 'stub-']],
                ],
            ],
        ]);
    }
}

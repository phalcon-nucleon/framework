<?php

declare(strict_types=1);

namespace Neutrino\Support\Facades;

use Neutrino\Constants\Services;

/**
 * The `cache` service ({@see \Neutrino\Cache\CacheStrategy}).
 *
 * @method static \Phalcon\Cache\CacheInterface uses(?string $use = null) Switches to a store, and returns it
 * @method static mixed get(string $key, mixed $defaultValue = null)
 * @method static bool set(string $key, mixed $value, \DateInterval|int|null $ttl = null)
 * @method static bool has(string $key)
 * @method static bool delete(string $key)
 * @method static bool clear()
 * @method static iterable<string, mixed> getMultiple(iterable<string> $keys, mixed $defaultValue = null)
 * @method static bool setMultiple(iterable<string, mixed> $values, \DateInterval|int|null $ttl = null)
 * @method static bool deleteMultiple(iterable<string> $keys)
 */
class Cache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Services::CACHE;
    }
}

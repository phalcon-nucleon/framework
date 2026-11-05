<?php

declare(strict_types=1);

namespace Neutrino\Security;

use Closure;
use Neutrino\Constants\Services;
use Phalcon\Cache\Adapter;
use Phalcon\Cache\Cache as PhalconCache;
use Phalcon\Cache\CacheInterface;
use Phalcon\Config\Config;
use Phalcon\Di\Injectable;
use RuntimeException;

/**
 * Counts the attempts of a key in a fixed window: the window starts with the first hit and lasts
 * `$decaySeconds`, the following hits do not extend it.
 *
 * The counters are kept in a cache store: `$store`, else `security.throttle.store`, else the default store
 * (`cache.default`). A dedicated store keeps them out of the application cache.
 *
 * The increment is atomic on the `redis`, `rediscluster`, `apcu` and `libmemcached` adapters. On the other
 * adapters (`memory`, `stream`…), two concurrent requests can count once. The first hits of a new window can
 * also overwrite each other: a window can count one or two hits less than received.
 */
final class RateLimiter extends Injectable
{
    /**
     * Adapters with an atomic `increment()` that keeps the expiration of the key.
     *
     * @var list<class-string>
     */
    private const array ATOMIC = [Adapter\Redis::class, Adapter\RedisCluster::class, Adapter\Apcu::class, Adapter\Libmemcached::class];

    private ?CacheInterface $cache = null;

    /** @var Closure(): int */
    private readonly Closure $clock;

    /**
     * @param string               $name  Name of the limiter, part of the cache keys
     * @param string|null          $store Cache store of the counters
     * @param (Closure(): int)|null $clock Current timestamp (tests)
     */
    public function __construct(private readonly string $name = '', private readonly ?string $store = null, ?Closure $clock = null)
    {
        $this->clock = $clock ?? time(...);
    }

    /**
     * Counts an attempt, and returns the number of attempts of the window.
     */
    public function hit(string $key, int $decaySeconds = 60): int
    {
        $now = ($this->clock)();
        [$counter, $timer] = $this->keys($key);
        $cache = $this->cache();
        $end = self::int($cache->get($timer));

        if ($end === null || $end <= $now) {
            $cache->set($timer, $now + $decaySeconds, $decaySeconds);
            $cache->set($counter, 1, $decaySeconds);

            return 1;
        }

        return $this->increment($cache, $counter, $end - $now);
    }

    /**
     * Counts an attempt if the limit is not reached, and checks the count after the increment: with an atomic
     * store, concurrent requests cannot exceed the limit. Returns the attempts left after this one, or `null`
     * when the attempt is refused.
     */
    public function attempt(string $key, int $maxAttempts, int $decaySeconds = 60): ?int
    {
        if ($this->tooManyAttempts($key, $maxAttempts)) {
            return null;
        }

        $attempts = $this->hit($key, $decaySeconds);

        return $attempts > $maxAttempts ? null : $maxAttempts - $attempts;
    }

    /**
     * Number of attempts in the current window.
     */
    public function attempts(string $key): int
    {
        if ($this->windowEnd($key) === null) {
            return 0;
        }

        return self::int($this->cache()->get($this->keys($key)[0])) ?? 0;
    }

    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return $this->attempts($key) >= $maxAttempts;
    }

    public function retriesLeft(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - $this->attempts($key));
    }

    /**
     * Seconds until the end of the current window: when the attempts are counted from zero again.
     */
    public function availableIn(string $key): int
    {
        $end = $this->windowEnd($key);

        return $end === null ? 0 : $end - ($this->clock)();
    }

    /**
     * Resets the attempts, in the current window.
     */
    public function resetAttempts(string $key): bool
    {
        return $this->cache()->delete($this->keys($key)[0]);
    }

    /**
     * Removes the attempts and the window.
     */
    public function clear(string $key): void
    {
        $this->cache()->deleteMultiple($this->keys($key));
    }

    private function windowEnd(string $key): ?int
    {
        $end = self::int($this->cache()->get($this->keys($key)[1]));

        return $end !== null && $end > ($this->clock)() ? $end : null;
    }

    /**
     * Some adapters store the integers raw and return them as strings (Redis).
     */
    private static function int(mixed $value): ?int
    {
        return is_int($value) || (is_string($value) && ctype_digit($value)) ? (int) $value : null;
    }

    private function increment(CacheInterface $cache, string $key, int $ttl): int
    {
        if ($cache instanceof PhalconCache) {
            $adapter = $cache->getAdapter();

            foreach (self::ATOMIC as $atomic) {
                if ($adapter instanceof $atomic) {
                    $value = $adapter->increment($key);

                    if (is_int($value)) {
                        if ($value === 1) {
                            // The counter was missing (reset, evicted): the increment created it without expiration.
                            $cache->set($key, 1, $ttl);
                        }

                        return $value;
                    }

                    break;
                }
            }
        }

        $value = (self::int($cache->get($key)) ?? 0) + 1;
        $cache->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Cache keys of the counter and of the window: hashed, the PSR-16 keys forbid `{}()/\@:`.
     *
     * @return array{string, string}
     */
    private function keys(string $key): array
    {
        $hash = 'throttle-' . hash('xxh128', $this->name . "\0" . $key);

        return [$hash, $hash . '-window'];
    }

    private function cache(): CacheInterface
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $di = $this->getDI();
        /** @var Config $config */
        $config = $di->getShared(Services::CONFIG);
        $store = $this->store ?? $config->path('security.throttle.store') ?? $config->path('cache.default');

        if (!is_string($store) || $store === '') {
            throw new RuntimeException('RateLimiter: no cache store, set "security.throttle.store" or "cache.default".');
        }

        $cache = $di->getShared(Services::CACHE . '.' . $store);

        if (!$cache instanceof CacheInterface) {
            throw new RuntimeException('RateLimiter: the cache store "' . $store . '" must implement ' . CacheInterface::class . '.');
        }

        return $this->cache = $cache;
    }
}

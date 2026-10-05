<?php

declare(strict_types=1);

namespace Neutrino\Cache;

use DateInterval;
use Neutrino\Constants\Services;
use Neutrino\Support\DesignPatterns\Strategy\MagicCallStrategyTrait;
use Neutrino\Support\DesignPatterns\Strategy\StrategyInterface;
use Neutrino\Support\DesignPatterns\Strategy\StrategyTrait;
use Phalcon\Cache\CacheInterface;
use Phalcon\Di\DiInterface;
use Phalcon\Di\Injectable;
use RuntimeException;

/**
 * The `cache` service: delegates to the default store, or to the store chosen with `uses()`.
 *
 * Each store is the `cache.<store>` service, built on its first use.
 */
final class CacheStrategy extends Injectable implements StrategyInterface, CacheInterface
{
    use StrategyTrait {
        uses as private useStore;
    }
    use MagicCallStrategyTrait;

    /**
     * @param string|null  $default Default store
     * @param list<string> $stores  Names of the stores: the `cache.<store>` services
     */
    public function __construct(DiInterface $container, ?string $default, array $stores)
    {
        $this->setDI($container);
        $this->default = $default;
        $this->supported = $stores;
    }

    /**
     * Returns the current store, after switching to `$use` when given.
     */
    public function uses(?string $use = null): CacheInterface
    {
        /** @var CacheInterface */
        return $this->useStore($use);
    }

    /**
     * @param mixed $defaultValue
     */
    public function get(string $key, $defaultValue = null): mixed
    {
        return $this->store()->get($key, $defaultValue);
    }

    /**
     * @param mixed                 $value
     * @param DateInterval|int|null $ttl
     */
    public function set(string $key, $value, $ttl = null): bool
    {
        return $this->store()->set($key, $value, $ttl);
    }

    public function has(string $key): bool
    {
        return $this->store()->has($key);
    }

    public function delete(string $key): bool
    {
        return $this->store()->delete($key);
    }

    public function clear(): bool
    {
        return $this->store()->clear();
    }

    /**
     * @param iterable<int|string, string> $keys
     * @param mixed            $defaultValue
     *
     * @return iterable<string, mixed>
     */
    public function getMultiple($keys, $defaultValue = null): mixed
    {
        return $this->store()->getMultiple($keys, $defaultValue);
    }

    /**
     * @param iterable<string, mixed> $values
     * @param DateInterval|int|null   $ttl
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return $this->store()->setMultiple($values, $ttl);
    }

    /**
     * @param iterable<int|string, string> $keys
     */
    public function deleteMultiple($keys): bool
    {
        return $this->store()->deleteMultiple($keys);
    }

    protected function make(string $use): CacheInterface
    {
        $store = $this->getDI()->getShared(Services::CACHE . '.' . $use);

        if (!$store instanceof CacheInterface) {
            throw new RuntimeException('The cache store "' . $use . '" must implement ' . CacheInterface::class . '.');
        }

        return $store;
    }

    private function store(): CacheInterface
    {
        /** @var CacheInterface */
        return $this->adapter ?? $this->useStore();
    }
}

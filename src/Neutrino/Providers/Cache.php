<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Cache\CacheStrategy;
use Neutrino\Constants\Services;
use Neutrino\Interfaces\Providable;
use Phalcon\Cache\Adapter;
use Phalcon\Cache\Adapter\AdapterInterface;
use Phalcon\Cache\Cache as PhalconCache;
use Phalcon\Config\Config;
use Phalcon\Di\Injectable;
use Phalcon\Di\Service;
use Phalcon\Storage\SerializerFactory;
use RuntimeException;
use Throwable;

/**
 * The `cache` service ({@see CacheStrategy}) and one `cache.<store>` service per store of `cache.stores`.
 *
 * A store is `['adapter' => 'redis', 'serializer' => 'php', 'options' => [...]]`:
 * - `adapter`: an adapter of the Phalcon cache (see {@see Cache::ADAPTERS}), or a class implementing
 *   {@see AdapterInterface}, built with `new $class(SerializerFactory $factory, array $options)`;
 * - `serializer`: `php` (default), `json`, `base64`, `igbinary`, `msgpack`, `none`…;
 * - `options`: the options of the adapter (`storageDir` for `stream`, `host` and `port` for `redis`, `lifetime`…).
 *
 * Nothing is built at boot: each store is built on its first use.
 */
class Cache extends Injectable implements Providable
{
    /**
     * Adapters of the Phalcon cache, by name. Built directly: the Phalcon factory costs more than the adapter.
     *
     * @var array<string, class-string<AdapterInterface>>
     */
    public const array ADAPTERS = [
        'apcu'         => Adapter\Apcu::class,
        'libmemcached' => Adapter\Libmemcached::class,
        'memory'       => Adapter\Memory::class,
        'redis'        => Adapter\Redis::class,
        'rediscluster' => Adapter\RedisCluster::class,
        'stream'       => Adapter\Stream::class,
        'weak'         => Adapter\Weak::class,
    ];

    /**
     * Nucleon 1.3 backends renamed in 2.0.
     *
     * @var array<string, string>
     */
    private const array RENAMED = ['file' => 'stream', 'apc' => 'apcu'];

    private ?SerializerFactory $serializerFactory = null;

    public function registering(): void
    {
        $di = $this->getDI();

        /** @var Config $config */
        $config = $di->getShared(Services::CONFIG);
        $default = $config->path('cache.default');
        $default = is_string($default) && $default !== '' ? $default : null;
        $stores = $config->path('cache.stores');
        $stores = $stores instanceof Config ? $stores : [];
        $names = [];
        $self = $this;

        foreach ($stores as $name => $store) {
            $names[] = $name = (string) $name;

            // Phalcon binds closure definitions to the container: $this would be the Di, not the provider.
            $di->setService(Services::CACHE . '.' . $name, new Service(function () use ($self, $name, $store): PhalconCache {
                return $self->makeStore($name, $store instanceof Config ? $store->toArray() : []);
            }, true));
        }

        $di->setService(Services::CACHE, new Service(function () use ($di, $default, $names): CacheStrategy {
            return new CacheStrategy($di, $default, $names);
        }, true));
    }

    /**
     * Builds a cache store.
     *
     * @param array<mixed> $config `['adapter' => …, 'serializer' => …, 'options' => […]]`
     */
    public function makeStore(string $name, array $config): PhalconCache
    {
        foreach (['driver', 'backend', 'frontend'] as $key) {
            if (array_key_exists($key, $config)) {
                throw new RuntimeException(
                    "Cache store \"$name\": the \"$key\" key of Nucleon 1.3 is no longer supported. "
                    . "Use ['adapter' => 'memory|stream|redis|…', 'serializer' => 'php|json|…', 'options' => [...]] (see UPGRADING-2.0.md).",
                );
            }
        }

        $adapter = $config['adapter'] ?? null;
        if (!is_string($adapter) || $adapter === '') {
            throw new RuntimeException("Cache store \"$name\": no adapter.");
        }

        $options = $config['options'] ?? [];
        if (!is_array($options)) {
            throw new RuntimeException("Cache store \"$name\": \"options\" must be an array.");
        }
        if (isset($config['serializer'])) {
            $options['defaultSerializer'] = $config['serializer'];
        }

        $known = strtolower($adapter);

        try {
            if (isset(self::ADAPTERS[$known]) || (class_exists($adapter) && is_subclass_of($adapter, AdapterInterface::class))) {
                /** @var class-string<AdapterInterface> $class The options of the config are checked by the adapter. */
                $class = self::ADAPTERS[$known] ?? $adapter;
                $instance = new $class($this->serializerFactory(), $options);
            } else {
                throw new RuntimeException(isset(self::RENAMED[$known])
                    ? "unknown adapter \"$adapter\", use \"" . self::RENAMED[$known] . '".'
                    : "unknown adapter \"$adapter\". Supported adapters: " . implode(', ', array_keys(self::ADAPTERS))
                    . ', or a class implementing ' . AdapterInterface::class . '.');
            }
        } catch (Throwable $e) {
            throw new RuntimeException("Cache store \"$name\": " . $e->getMessage(), 0, $e);
        }

        return new PhalconCache($instance);
    }

    private function serializerFactory(): SerializerFactory
    {
        return $this->serializerFactory ??= new SerializerFactory();
    }
}

<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Model\MetaDataStrategy;
use Neutrino\Support\Provider;
use Phalcon\Cache\AdapterFactory;
use Phalcon\Config\Config;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\MetaDataInterface;
use Phalcon\Storage\SerializerFactory;
use RuntimeException;

/**
 * The `modelsMetadata` service, with the {@see MetaDataStrategy} of the Nucleon models.
 *
 * `models.metadata.adapter`: `memory` (default, rebuilt on each request), `apcu`, `stream` (`metaDataDir`),
 * `redis` or `libmemcached`, or a class implementing {@see MetaDataInterface}; `models.metadata.options`: its
 * options. The description of a Nucleon model costs less than reading a cache: keep `memory` for them. A cache
 * (warmed up by `model:cache`) pays off for the models read by introspection.
 */
class ModelsMetaData extends Provider
{
    protected string $name = Services::MODELS_METADATA;

    protected bool $shared = true;

    protected array $aliases = [MetaDataInterface::class];

    protected function register(): MetaDataInterface
    {
        /** @var Config $config */
        $config = $this->getDI()->getShared(Services::CONFIG);
        $adapter = $config->path('models.metadata.adapter', 'memory');
        $options = $config->path('models.metadata.options');
        /** @var array<string, mixed> $options */
        $options = $options instanceof Config ? $options->toArray() : [];

        $metaData = match (is_string($adapter) ? strtolower($adapter) : null) {
            'memory'       => new MetaData\Memory(),
            'apcu'         => new MetaData\Apcu(self::cacheFactory(), $options),
            'stream'       => new MetaData\Stream($options),
            'redis'        => new MetaData\Redis(self::cacheFactory(), $options),
            'libmemcached' => new MetaData\Libmemcached(self::cacheFactory(), $options),
            default        => is_string($adapter) && class_exists($adapter) && is_subclass_of($adapter, MetaDataInterface::class)
                ? new $adapter()
                : throw new RuntimeException('Models meta-data: unknown adapter, use memory, apcu, stream, redis, libmemcached or a class implementing ' . MetaDataInterface::class . '.'),
        };

        if ($metaData instanceof MetaData) {
            $metaData->setStrategy(new MetaDataStrategy());
        }

        return $metaData;
    }

    private static function cacheFactory(): AdapterFactory
    {
        return new AdapterFactory(new SerializerFactory());
    }
}

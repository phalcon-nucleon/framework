<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;
use Phalcon\Annotations\Adapter\AdapterInterface;
use Phalcon\Annotations\Adapter\Apcu;
use Phalcon\Annotations\Adapter\Memory;
use Phalcon\Annotations\Adapter\Stream;
use Phalcon\Config\Config;
use RuntimeException;

/**
 * The `annotations` service: `annotations.adapter` is `memory` (default), `apcu` or `stream` (recommended in
 * production), or a class implementing {@see AdapterInterface}; `annotations.options` are its options
 * (`annotationsDir` for `stream`, `prefix` and `lifetime` for `apcu`).
 */
class Annotations extends Provider
{
    protected string $name = Services::ANNOTATIONS;

    protected bool $shared = true;

    protected array $aliases = [AdapterInterface::class];

    protected function register(): AdapterInterface
    {
        /** @var Config $config */
        $config = $this->getDI()->getShared(Services::CONFIG);

        $adapter = $config->path('annotations.adapter', 'memory');
        $options = $config->path('annotations.options');
        /** @var array<string, mixed> $options */
        $options = $options instanceof Config ? $options->toArray() : [];

        return match (is_string($adapter) ? strtolower($adapter) : null) {
            'memory' => new Memory($options),
            'apcu'   => new Apcu($options),
            'stream' => new Stream($options),
            default  => is_string($adapter) && class_exists($adapter) && is_subclass_of($adapter, AdapterInterface::class)
                ? new $adapter($options)
                : throw new RuntimeException('Annotations: unknown adapter, use memory, apcu, stream or a class implementing ' . AdapterInterface::class . '.'),
        };
    }
}

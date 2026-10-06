<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\HttpClient\HttpClient as Client;
use Neutrino\HttpClient\HttpClientInterface;
use Neutrino\HttpClient\Transport;
use Neutrino\HttpClient\Transport\CurlTransport;
use Neutrino\HttpClient\Transport\StreamTransport;
use Neutrino\Support\Provider;
use Phalcon\Config\ConfigInterface;
use RuntimeException;

/**
 * The `httpClient` service ({@see Client}), with the default options of `http_client`:
 *
 * ```php
 * 'http_client' => [
 *     'transport' => 'curl',   // optional: `curl` (default when ext-curl is loaded), `stream`, or a Transport class
 *     'timeout'   => 10,       // the options of Neutrino\HttpClient\Options::DEFAULTS
 *     'headers'   => ['user-agent' => 'my-app'],
 * ],
 * ```
 */
class HttpClient extends Provider
{
    protected string $name = Services::HTTP_CLIENT;

    protected bool $shared = true;

    protected array $aliases = [HttpClientInterface::class, Client::class];

    protected function register(): Client
    {
        $config = $this->getDI()->has(Services::CONFIG) ? $this->getDI()->getShared(Services::CONFIG) : null;
        $options = $config instanceof ConfigInterface ? $config->path('http_client') : null;
        $options = $options instanceof ConfigInterface ? $options->toArray() : (is_array($options) ? $options : []);

        /** @var array<string, mixed> $options */
        $transport = $options['transport'] ?? null;
        unset($options['transport']);

        return new Client($options, match (true) {
            $transport === null => null,
            $transport === 'curl' => new CurlTransport(),
            $transport === 'stream' => new StreamTransport(),
            is_string($transport) && is_subclass_of($transport, Transport::class) => new $transport(),
            default => throw new RuntimeException('http_client.transport: use curl, stream or a class implementing ' . Transport::class . '.'),
        });
    }
}

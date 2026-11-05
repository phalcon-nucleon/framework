<?php

declare(strict_types=1);

namespace Neutrino\HttpClient;

use Neutrino\HttpClient\Exception\InvalidArgumentException;
use Neutrino\HttpClient\Transport\CurlTransport;
use Neutrino\HttpClient\Transport\StreamTransport;

/**
 * The HTTP client.
 *
 * ```php
 * $client = new HttpClient(['base_uri' => 'https://api.example.com', 'auth_bearer' => $token]);
 *
 * $users = $client->request('GET', '/users', ['query' => ['page' => 2]])->toArray();
 * ```
 */
final class HttpClient implements HttpClientInterface
{
    private readonly Transport $transport;

    /** @var array<string, mixed> */
    private array $options;

    /**
     * @param array<string, mixed> $options   Default options of the requests (see {@see Options::DEFAULTS})
     * @param Transport|null       $transport cURL when `ext-curl` is loaded, the PHP streams otherwise
     */
    public function __construct(array $options = [], ?Transport $transport = null)
    {
        $this->options = Options::merge(Options::DEFAULTS, $options);
        $this->transport = $transport ?? (extension_loaded('curl') ? new CurlTransport() : new StreamTransport());
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $options = Options::merge($this->options, $options);
        $maxRedirects = $options['max_redirects'];

        if (!is_int($maxRedirects) || $maxRedirects < 0) {
            throw new InvalidArgumentException('Option "max_redirects": a positive integer.');
        }

        return new Response(
            $this->transport,
            Options::prepare($method, $url, $options),
            $maxRedirects,
            (bool) $options['buffer'],
            Options::onProgress($options),
            $options['user_data'],
        );
    }

    public function withOptions(array $options): static
    {
        $client = clone $this;
        $client->options = Options::merge($this->options, $options);

        return $client;
    }

    public function getTransport(): Transport
    {
        return $this->transport;
    }
}

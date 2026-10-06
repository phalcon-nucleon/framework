<?php

declare(strict_types=1);

namespace Neutrino\HttpClient;

use Neutrino\HttpClient\Exception\InvalidArgumentException;

/**
 * An HTTP client: immutable, `withOptions()` returns a copy.
 *
 * The options (a subset of `symfony/http-client`'s, same names) are listed in {@see Options::DEFAULTS}.
 */
interface HttpClientInterface
{
    /**
     * Prepares a request. It is sent when the response is first read (or destroyed).
     *
     * @param array<string, mixed> $options
     *
     * @throws InvalidArgumentException An unknown option, an invalid value, a URL other than http(s)
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface;

    /**
     * A copy of the client with other default options (merged with the current ones).
     *
     * @param array<string, mixed> $options
     */
    public function withOptions(array $options): static;
}

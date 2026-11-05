<?php

declare(strict_types=1);

namespace Neutrino\HttpClient;

use Generator;
use Neutrino\HttpClient\Exception\ClientException;
use Neutrino\HttpClient\Exception\DecodingException;
use Neutrino\HttpClient\Exception\RedirectionException;
use Neutrino\HttpClient\Exception\ServerException;
use Neutrino\HttpClient\Exception\TransportException;

/**
 * A response, read on first access. The methods with `$throw` raise an exception on a 3xx, 4xx or 5xx status.
 */
interface ResponseInterface
{
    /**
     * @throws TransportException
     */
    public function getStatusCode(): int;

    /**
     * The headers, by lower-case name.
     *
     * @return array<string, list<string>>
     *
     * @throws TransportException|RedirectionException|ClientException|ServerException
     */
    public function getHeaders(bool $throw = true): array;

    /**
     * The body.
     *
     * @throws TransportException|RedirectionException|ClientException|ServerException
     */
    public function getContent(bool $throw = true): string;

    /**
     * The body decoded from JSON.
     *
     * @return array<mixed>
     *
     * @throws TransportException|RedirectionException|ClientException|ServerException|DecodingException
     */
    public function toArray(bool $throw = true): array;

    /**
     * The body, chunk by chunk as it is received (with `buffer: false`, it is not kept).
     *
     * @return Generator<int, string>
     *
     * @throws TransportException|RedirectionException|ClientException|ServerException
     */
    public function chunks(bool $throw = true): Generator;

    /**
     * Information on the exchange, all of them without `$type`: `http_code`, `http_method`, `url`, `redirect_count`,
     * `redirect_url`, `start_time`, `total_time`, `size_download`, `user_data`. Does not send the request.
     */
    public function getInfo(?string $type = null): mixed;
}

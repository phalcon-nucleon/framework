<?php

declare(strict_types=1);

namespace Neutrino\Support\Facades;

use Neutrino\Constants\Services;

/**
 * The `httpClient` service ({@see \Neutrino\HttpClient\HttpClient}).
 *
 * In a test, `Http::swap(new HttpClient([], new MockTransport([...])))` answers with mock responses.
 *
 * @method static \Neutrino\HttpClient\ResponseInterface request(string $method, string $url, array<string, mixed> $options = [])
 * @method static \Neutrino\HttpClient\HttpClient withOptions(array<string, mixed> $options)
 */
class Http extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Services::HTTP_CLIENT;
    }
}

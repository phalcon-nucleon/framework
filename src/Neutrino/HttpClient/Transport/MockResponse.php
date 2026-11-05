<?php

declare(strict_types=1);

namespace Neutrino\HttpClient\Transport;

use Neutrino\HttpClient\Exception\TransportException;

/**
 * A response returned by a {@see MockTransport}.
 *
 * ```php
 * new MockResponse('{"id": 1}', 201, ['content-type' => 'application/json']);
 * new MockResponse(['chunk 1', 'chunk 2']);   // the body received in chunks
 * MockResponse::error('Connection refused');  // a network error
 * ```
 */
final readonly class MockResponse
{
    /** @var list<string> */
    public array $chunks;

    /** @var array<string, list<string>> */
    public array $headers;

    /**
     * @param string|iterable<string>                    $body
     * @param array<string, string|list<string>>         $headers
     */
    public function __construct(string|iterable $body = '', public int $status = 200, array $headers = [], public ?string $error = null)
    {
        $this->chunks = is_string($body) ? [$body] : array_map(strval(...), iterator_to_array($body, false));

        $normalized = [];
        foreach ($headers as $name => $values) {
            $normalized[strtolower($name)] = is_array($values) ? $values : [$values];
        }
        $this->headers = $normalized;
    }

    /**
     * A network error: the request throws a {@see TransportException}.
     */
    public static function error(string $message): self
    {
        return new self('', 0, [], $message);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self((string) json_encode($data), $status, ['content-type' => 'application/json']);
    }
}

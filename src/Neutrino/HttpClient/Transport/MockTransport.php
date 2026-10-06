<?php

declare(strict_types=1);

namespace Neutrino\HttpClient\Transport;

use Closure;
use Generator;
use Neutrino\HttpClient\Exception\TransportException;
use Neutrino\HttpClient\Head;
use Neutrino\HttpClient\Request;
use Neutrino\HttpClient\Transport;

/**
 * A transport for the tests: it answers with {@see MockResponse}s and records the requests sent.
 *
 * ```php
 * $transport = new MockTransport([new MockResponse('ok'), MockResponse::json(['id' => 1], 201)]);
 * // or: new MockTransport(fn (Request $request): MockResponse => new MockResponse($request->url));
 *
 * Http::swap(new HttpClient([], $transport));
 * // ...
 * $transport->getRequests()[0]->url;
 * ```
 */
final class MockTransport implements Transport
{
    /** @var list<Request> */
    private array $requests = [];

    /** @var list<MockResponse>|Closure */
    private array|Closure $responses;

    /**
     * @param iterable<MockResponse>|MockResponse|callable(Request): MockResponse $responses Answered in order, or by
     *                                                                                       a callback
     */
    public function __construct(iterable|MockResponse|callable $responses = [])
    {
        if (is_callable($responses)) {
            $this->responses = $responses(...);
        } else {
            $this->responses = $responses instanceof MockResponse ? [$responses] : iterator_to_array($responses, false);
        }
    }

    public function exchange(Request $request): Generator
    {
        $this->requests[] = $request;

        if ($this->responses instanceof Closure) {
            $response = ($this->responses)($request);

            if (!$response instanceof MockResponse) {
                throw new TransportException('The mock callback must return a ' . MockResponse::class . '.');
            }
        } else {
            $response = array_shift($this->responses) ?? throw new TransportException('No more mock response for ' . $request->method . ' "' . $request->url . '".');
        }

        if ($response->error !== null) {
            throw new TransportException($response->error);
        }

        yield new Head($response->status, $response->headers);

        foreach ($response->chunks as $chunk) {
            yield $chunk;
        }
    }

    /**
     * The requests sent, redirections included.
     *
     * @return list<Request>
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    public function getRequestsCount(): int
    {
        return count($this->requests);
    }
}

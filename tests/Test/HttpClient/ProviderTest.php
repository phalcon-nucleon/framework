<?php

declare(strict_types=1);

namespace Test\HttpClient;

use Neutrino\Config\Config;
use Neutrino\Constants\Services;
use Neutrino\HttpClient\HttpClient;
use Neutrino\HttpClient\HttpClientInterface;
use Neutrino\HttpClient\Request;
use Neutrino\HttpClient\Transport\MockResponse;
use Neutrino\HttpClient\Transport\MockTransport;
use Neutrino\HttpClient\Transport\StreamTransport;
use Neutrino\Providers\HttpClient as HttpClientProvider;
use Neutrino\Support\Facades\Http;
use Test\TestCase\TestCase;

final class ProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        self::getConfig()->remove('http_client');
        Http::clearResolvedInstances();

        parent::tearDown();
    }

    public function testService(): void
    {
        $this->register(['transport' => 'stream', 'timeout' => 3]);

        $client = $this->getDI()->getShared(Services::HTTP_CLIENT);

        $this->assertInstanceOf(HttpClient::class, $client);
        $this->assertInstanceOf(StreamTransport::class, $client->getTransport());
        $this->assertSame($client, $this->getDI()->getShared(HttpClientInterface::class));
        $this->assertSame($client, $this->getDI()->getShared(HttpClient::class));
    }

    public function testDefaultOptionsOfTheConfig(): void
    {
        $this->register(['transport' => MockTransport::class, 'base_uri' => 'https://api.example.com', 'headers' => ['user-agent' => 'app']]);

        $client = $this->getDI()->getShared(Services::HTTP_CLIENT);
        $transport = $client->getTransport();
        $this->assertInstanceOf(MockTransport::class, $transport);

        $client->request('GET', '/x');

        $this->assertSame('https://api.example.com/x', $transport->getRequests()[0]->url);
        $this->assertSame(['user-agent' => ['app']], $transport->getRequests()[0]->headers);
    }

    public function testInvalidTransport(): void
    {
        $this->register(['transport' => 'socket']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('http_client.transport: use curl, stream or a class implementing');

        $this->getDI()->getShared(Services::HTTP_CLIENT);
    }

    /**
     * The example of the documentation: a test of an app with mock responses.
     */
    public function testFacadeWithMockResponses(): void
    {
        $this->register([]);
        $transport = new MockTransport(static fn(Request $request): MockResponse => MockResponse::json(['url' => $request->url]));

        Http::swap(new HttpClient([], $transport));

        $this->assertSame(['url' => 'https://example.com/users'], Http::request('GET', 'https://example.com/users')->toArray());
        $this->assertSame(1, $transport->getRequestsCount());
    }

    /**
     * @param array<string, mixed> $config
     */
    private function register(array $config): void
    {
        self::getConfig()->merge(new Config(['http_client' => $config]));

        $provider = new HttpClientProvider();
        $provider->setDI($this->getDI());
        $provider->registering();
    }
}

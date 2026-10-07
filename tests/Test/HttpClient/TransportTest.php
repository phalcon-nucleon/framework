<?php

declare(strict_types=1);

namespace Test\HttpClient;

use Neutrino\HttpClient\Exception\ClientException;
use Neutrino\HttpClient\Exception\InvalidArgumentException;
use Neutrino\HttpClient\Exception\ServerException;
use Neutrino\HttpClient\Exception\TransportException;
use Neutrino\HttpClient\HttpClient;
use Neutrino\HttpClient\Transport;
use Neutrino\HttpClient\Transport\CurlTransport;
use Neutrino\HttpClient\Transport\StreamTransport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The cURL and stream transports, against local servers.
 */
final class TransportTest extends TestCase
{
    public static function tearDownAfterClass(): void
    {
        LocalServer::stopAll();
    }

    /**
     * @return iterable<string, array{Transport}>
     */
    public static function transports(): iterable
    {
        yield 'curl' => [new CurlTransport()];
        yield 'stream' => [new StreamTransport()];
    }

    #[DataProvider('transports')]
    public function testGet(Transport $transport): void
    {
        $response = self::client($transport)->request('GET', '/echo', ['query' => ['a' => 'b c'], 'headers' => ['X-Custom' => 'value']]);

        $echo = $response->toArray();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['application/json'], $response->getHeaders()['content-type']);
        $this->assertSame('GET', $echo['method']);
        $this->assertSame('/echo?a=b%20c', $echo['uri']);
        $this->assertSame(['a' => 'b c'], $echo['query']);
        $this->assertSame('value', $echo['headers']['x-custom']);
        $this->assertSame(substr(LocalServer::url(), 7), $echo['headers']['host']);
    }

    /**
     * @return iterable<string, array{Transport, string, array<string, mixed>, string, string}>
     */
    public static function bodies(): iterable
    {
        foreach (self::transports() as $name => [$transport]) {
            yield $name . ' json' => [$transport, 'POST', ['json' => ['a' => 1]], '{"a":1}', 'application/json'];
            yield $name . ' form' => [$transport, 'PUT', ['body' => ['a' => 'b c']], 'a=b+c', 'application/x-www-form-urlencoded'];
            yield $name . ' raw' => [$transport, 'PATCH', ['body' => 'raw', 'headers' => ['content-type' => 'text/plain']], 'raw', 'text/plain'];
            yield $name . ' delete' => [$transport, 'DELETE', [], '', ''];
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('bodies')]
    public function testBody(Transport $transport, string $method, array $options, string $body, string $contentType): void
    {
        $echo = self::client($transport)->request($method, '/echo', $options)->toArray();

        $this->assertSame($method, $echo['method']);
        $this->assertSame($body, $echo['body']);
        $this->assertSame($contentType, $echo['headers']['content-type'] ?? '');
    }

    #[DataProvider('transports')]
    public function testHead(Transport $transport): void
    {
        $response = self::client($transport)->request('HEAD', '/json');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['application/json'], $response->getHeaders()['content-type']);
        $this->assertSame('', $response->getContent());
    }

    #[DataProvider('transports')]
    public function testErrorStatuses(Transport $transport): void
    {
        $client = self::client($transport);

        $this->assertSame('status 404', $client->request('GET', '/status/404')->getContent(false));
        $this->assertSame(500, $client->request('GET', '/status/500')->getStatusCode());

        $this->expectException(ClientException::class);

        $client->request('GET', '/status/418')->getContent();
    }

    #[DataProvider('transports')]
    public function testServerErrorException(Transport $transport): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('HTTP 503 returned for "' . LocalServer::url() . '/status/503".');

        self::client($transport)->request('GET', '/status/503')->toArray();
    }

    #[DataProvider('transports')]
    public function testRedirections(Transport $transport): void
    {
        $response = self::client($transport)->request('POST', '/redirect/302?to=' . urlencode('/redirect/307?to=/echo'), ['body' => 'data']);

        $echo = $response->toArray();
        $this->assertSame(2, $response->getInfo('redirect_count'));
        $this->assertSame(LocalServer::url() . '/echo', $response->getInfo('url'));
        // 302 turns the POST into a GET, 307 keeps it.
        $this->assertSame('GET', $echo['method']);
        $this->assertSame('', $echo['body']);
    }

    #[DataProvider('transports')]
    public function testAuthorizationNotSentToAnotherHost(Transport $transport): void
    {
        $client = self::client($transport, ['auth_basic' => 'user:secret', 'headers' => ['cookie' => 'session=1']]);

        $same = $client->request('GET', '/redirect/302?to=/echo')->toArray();
        $other = $client->request('GET', '/redirect/302?to=' . urlencode(LocalServer::url('b') . '/echo'))->toArray();

        $this->assertSame('Basic ' . base64_encode('user:secret'), $same['headers']['authorization']);
        $this->assertSame('session=1', $same['headers']['cookie']);
        $this->assertArrayNotHasKey('authorization', $other['headers']);
        $this->assertArrayNotHasKey('cookie', $other['headers']);
    }

    #[DataProvider('transports')]
    public function testRedirectionToFileRefused(Transport $transport): void
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('refused: Unsupported URL "file:///etc/passwd"');

        self::client($transport)->request('GET', '/redirect/302?to=' . urlencode('file:///etc/passwd'))->getContent();
    }

    #[DataProvider('transports')]
    public function testFileUrlRefused(Transport $transport): void
    {
        $this->expectException(InvalidArgumentException::class);

        self::client($transport)->request('GET', 'file:///etc/passwd');
    }

    #[DataProvider('transports')]
    public function testIdleTimeout(Transport $transport): void
    {
        $start = microtime(true);

        try {
            self::client($transport, ['timeout' => 1])->request('GET', '/slow?ms=3000')->getContent();
            $this->fail('No timeout');
        } catch (TransportException) {
            $this->assertLessThan(2.5, microtime(true) - $start);
        }
    }

    #[DataProvider('transports')]
    public function testMaxDuration(Transport $transport): void
    {
        $start = microtime(true);

        try {
            self::client($transport, ['max_duration' => 0.25])->request('GET', '/stream')->getContent();
            $this->fail('No timeout');
        } catch (TransportException $e) {
            $this->assertLessThan(0.45, microtime(true) - $start);
        }
    }

    #[DataProvider('transports')]
    public function testConnectionRefused(Transport $transport): void
    {
        $this->expectException(TransportException::class);

        self::client($transport, ['base_uri' => 'http://127.0.0.1:1'])->request('GET', '/')->getStatusCode();
    }

    #[DataProvider('transports')]
    public function testChunksAsTheyCome(Transport $transport): void
    {
        $times = [];
        $content = '';

        foreach (self::client($transport, ['buffer' => false])->request('GET', '/stream')->chunks() as $chunk) {
            $times[] = microtime(true);
            $content .= $chunk;
        }

        $this->assertSame("chunk 1\nchunk 2\nchunk 3\nchunk 4\nchunk 5\n", $content);
        // Received over ~0.4 s, not at once.
        $this->assertGreaterThan(0.3, end($times) - $times[0]);
    }

    #[DataProvider('transports')]
    public function testLargeBody(Transport $transport): void
    {
        $response = self::client($transport)->request('GET', '/bytes?size=1000000');

        $this->assertSame(1000000, strlen($response->getContent()));
        $this->assertSame(1000000, $response->getInfo('size_download'));
    }

    #[DataProvider('transports')]
    public function testHttp10(Transport $transport): void
    {
        $echo = self::client($transport, ['http_version' => '1.0'])->request('GET', '/echo')->toArray();

        $this->assertSame('GET', $echo['method']);
    }

    #[DataProvider('transports')]
    public function testProxy(Transport $transport): void
    {
        // The test server answers a proxied request (absolute URI) with its echo.
        $echo = (new HttpClient(['proxy' => LocalServer::url('b')], $transport))->request('GET', 'http://example.test/path?q=1')->toArray();

        $this->assertSame('http://example.test/path?q=1', $echo['uri']);

        $direct = (new HttpClient(['proxy' => 'http://127.0.0.1:1', 'no_proxy' => '127.0.0.1'], $transport))->request('GET', LocalServer::url() . '/echo')->toArray();

        $this->assertSame('/echo', $direct['uri']);
    }

    /**
     * The answer of the proxy to CONNECT is not the head of the response.
     */
    #[DataProvider('transports')]
    public function testHttpsThroughAProxy(Transport $transport): void
    {
        [$url, $certificate] = LocalServer::tls();

        $response = (new HttpClient(['proxy' => LocalServer::connectProxy(), 'cafile' => $certificate, 'timeout' => 5], $transport))->request('GET', $url . '/x');

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(['ok'], $response->getHeaders()['x-tls'] ?? null);
        $this->assertSame('tls ok', $response->getContent());
    }

    #[DataProvider('transports')]
    public function testWithoutTimeout(Transport $transport): void
    {
        $this->assertSame('slow', self::client($transport, ['timeout' => 0])->request('GET', '/slow?ms=100')->getContent());

        $previous = ini_set('default_socket_timeout', '-1');

        try {
            $client = new HttpClient(['base_uri' => LocalServer::url()], $transport);
            $this->assertSame('slow', $client->request('GET', '/slow?ms=100')->getContent());
        } finally {
            ini_set('default_socket_timeout', (string) $previous);
        }
    }

    #[DataProvider('transports')]
    public function testInvalidCertificate(Transport $transport): void
    {
        [$url, $certificate] = LocalServer::tls();

        try {
            (new HttpClient([], $transport))->request('GET', $url)->getContent();
            $this->fail('No TLS error');
        } catch (TransportException) {
            // Self-signed: refused.
        }

        $this->assertSame('tls ok', (new HttpClient(['verify_peer' => false, 'verify_host' => false], $transport))->request('GET', $url)->getContent());
        $this->assertSame('tls ok', (new HttpClient(['cafile' => $certificate], $transport))->request('GET', $url)->getContent());
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function client(Transport $transport, array $options = []): HttpClient
    {
        return new HttpClient($options + ['base_uri' => LocalServer::url(), 'timeout' => 5], $transport);
    }
}

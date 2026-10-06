<?php

declare(strict_types=1);

namespace Test\HttpClient;

use Neutrino\HttpClient\Exception\ClientException;
use Neutrino\HttpClient\Exception\DecodingException;
use Neutrino\HttpClient\Exception\HttpClientExceptionInterface;
use Neutrino\HttpClient\Exception\RedirectionException;
use Neutrino\HttpClient\Exception\ServerException;
use Neutrino\HttpClient\Exception\TransportException;
use Neutrino\HttpClient\HttpClient;
use Neutrino\HttpClient\Request;
use Neutrino\HttpClient\Transport\CurlTransport;
use Neutrino\HttpClient\Transport\MockResponse;
use Neutrino\HttpClient\Transport\MockTransport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The client and its responses, on the mock transport.
 */
final class HttpClientTest extends TestCase
{
    public function testRequest(): void
    {
        $transport = new MockTransport(MockResponse::json(['id' => 1], 201));
        $client = new HttpClient(['base_uri' => 'https://api.example.com', 'headers' => ['accept' => 'application/json']], $transport);

        $response = $client->request('POST', '/users', ['json' => ['name' => 'Ada']]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(['content-type' => ['application/json']], $response->getHeaders());
        $this->assertSame('{"id":1}', $response->getContent());
        $this->assertSame(['id' => 1], $response->toArray());

        $request = $transport->getRequests()[0];
        $this->assertSame('POST', $request->method);
        $this->assertSame('https://api.example.com/users', $request->url);
        $this->assertSame('{"name":"Ada"}', $request->body);
        $this->assertSame(['accept' => ['application/json'], 'content-type' => ['application/json']], $request->headers);
    }

    public function testWithOptionsIsACopy(): void
    {
        $transport = new MockTransport(static fn(Request $request): MockResponse => new MockResponse($request->url));
        $client = new HttpClient(['base_uri' => 'https://a.example.com'], $transport);
        $other = $client->withOptions(['base_uri' => 'https://b.example.com']);

        $this->assertNotSame($client, $other);
        $this->assertSame('https://a.example.com/x', $client->request('GET', '/x')->getContent());
        $this->assertSame('https://b.example.com/x', $other->request('GET', '/x')->getContent());
    }

    public function testDefaultTransport(): void
    {
        $this->assertInstanceOf(CurlTransport::class, (new HttpClient())->getTransport());
    }

    public function testSentOnFirstRead(): void
    {
        $transport = new MockTransport([new MockResponse('a'), new MockResponse('b')]);
        $client = new HttpClient([], $transport);

        $response = $client->request('GET', 'http://example.com');
        $this->assertSame(0, $transport->getRequestsCount());
        $this->assertSame('GET', $response->getInfo('http_method'));
        $this->assertSame(0, $transport->getRequestsCount());

        $response->getStatusCode();
        $this->assertSame(1, $transport->getRequestsCount());

        // A response never read is sent when destroyed.
        $client->request('POST', 'http://example.com/hook');
        $this->assertSame(2, $transport->getRequestsCount());
    }

    /**
     * @return iterable<array{int, class-string<HttpClientExceptionInterface>}>
     */
    public static function errorStatuses(): iterable
    {
        yield [304, RedirectionException::class];
        yield [404, ClientException::class];
        yield [422, ClientException::class];
        yield [500, ServerException::class];
        yield [503, ServerException::class];
    }

    /**
     * @param class-string<HttpClientExceptionInterface> $exception
     */
    #[DataProvider('errorStatuses')]
    public function testErrorStatuses(int $status, string $exception): void
    {
        $client = new HttpClient([], new MockTransport(static fn(): MockResponse => new MockResponse('{"error":"x"}', $status)));
        $response = $client->request('GET', 'http://example.com/x');

        $this->assertSame($status, $response->getStatusCode());
        $this->assertSame('{"error":"x"}', $response->getContent(false));
        $this->assertSame(['error' => 'x'], $response->toArray(false));
        $this->assertSame([], $response->getHeaders(false));

        foreach ([$response->getHeaders(...), $response->getContent(...), $response->toArray(...), static fn() => iterator_to_array($response->chunks())] as $read) {
            try {
                $read();
                $this->fail('No exception for ' . $status);
            } catch (HttpClientExceptionInterface $e) {
                $this->assertInstanceOf($exception, $e);
                $this->assertSame('HTTP ' . $status . ' returned for "http://example.com/x".', $e->getMessage());
                $this->assertSame($response, $e->getResponse()); // @phpstan-ignore method.notFound
            }
        }
    }

    public function testTransportError(): void
    {
        $response = (new HttpClient([], new MockTransport([MockResponse::error('Connection refused')])))->request('GET', 'http://example.com');

        try {
            $response->getStatusCode();
            $this->fail('No transport exception');
        } catch (TransportException $e) {
            $this->assertSame('Connection refused', $e->getMessage());
        }

        $this->expectException(TransportException::class);

        $response->getContent();
    }

    public function testDecodingErrors(): void
    {
        $client = new HttpClient([], new MockTransport([new MockResponse('{invalid'), new MockResponse('"string"')]));

        foreach (['Invalid JSON returned for "http://example.com/"', 'is not an array or an object'] as $message) {
            try {
                $client->request('GET', 'http://example.com')->toArray();
                $this->fail('No decoding exception');
            } catch (DecodingException $e) {
                $this->assertStringContainsString($message, $e->getMessage());
            }
        }
    }

    public function testChunks(): void
    {
        $client = new HttpClient([], new MockTransport(static fn(): MockResponse => new MockResponse(['a', 'b', '', 'c'])));

        $buffered = $client->request('GET', 'http://example.com');
        $this->assertSame(['a', 'b', 'c'], iterator_to_array($buffered->chunks(), false));
        $this->assertSame('abc', $buffered->getContent());
        $this->assertSame(['abc'], iterator_to_array($buffered->chunks(), false));

        $unbuffered = $client->request('GET', 'http://example.com', ['buffer' => false]);
        $this->assertSame(['a', 'b', 'c'], iterator_to_array($unbuffered->chunks(), false));

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('already read without buffer');

        $unbuffered->getContent();
    }

    public function testInfoAndProgress(): void
    {
        $progress = [];
        $client = new HttpClient([], new MockTransport(new MockResponse(['ab', 'cde'], 200, ['content-length' => '5'])));

        $response = $client->request('GET', 'http://example.com/x', [
            'user_data'   => ['id' => 7],
            'on_progress' => static function (int $downloaded, int $total, array $info) use (&$progress): void {
                $progress[] = [$downloaded, $total, $info['url']];
            },
        ]);
        $response->getContent();

        $this->assertSame([[2, 5, 'http://example.com/x'], [5, 5, 'http://example.com/x']], $progress);

        $info = $response->getInfo();
        $this->assertIsArray($info);
        $this->assertSame(200, $info['http_code']);
        $this->assertSame('http://example.com/x', $info['url']);
        $this->assertSame(0, $info['redirect_count']);
        $this->assertSame(5, $info['size_download']);
        $this->assertSame(['id' => 7], $info['user_data']);
        $this->assertGreaterThan(0, $info['start_time']);
        $this->assertGreaterThanOrEqual(0, $info['total_time']);
        $this->assertNull($response->getInfo('unknown'));
    }

    public function testRedirections(): void
    {
        $transport = new MockTransport([
            new MockResponse('', 301, ['location' => '/moved']),
            new MockResponse('', 302, ['location' => 'https://example.com/final?x=1']),
            new MockResponse('final'),
        ]);

        $response = (new HttpClient([], $transport))->request('GET', 'https://example.com/start');

        $this->assertSame('final', $response->getContent());
        $this->assertSame(2, $response->getInfo('redirect_count'));
        $this->assertSame('https://example.com/final?x=1', $response->getInfo('url'));
        $this->assertSame(
            ['https://example.com/start', 'https://example.com/moved', 'https://example.com/final?x=1'],
            array_map(static fn(Request $request): string => $request->url, $transport->getRequests()),
        );
    }

    /**
     * @return iterable<array{string, int, string, string}>
     */
    public static function redirectedMethods(): iterable
    {
        yield ['POST', 301, 'GET', ''];
        yield ['POST', 302, 'GET', ''];
        yield ['PUT', 303, 'GET', ''];
        yield ['PUT', 302, 'PUT', 'body'];
        yield ['POST', 307, 'POST', 'body'];
        yield ['DELETE', 308, 'DELETE', 'body'];
        yield ['HEAD', 303, 'HEAD', ''];
    }

    #[DataProvider('redirectedMethods')]
    public function testRedirectedMethod(string $method, int $status, string $redirectedMethod, string $redirectedBody): void
    {
        $transport = new MockTransport([new MockResponse('', $status, ['location' => '/next']), new MockResponse()]);

        (new HttpClient([], $transport))->request($method, 'http://example.com', $method === 'HEAD' ? [] : ['body' => 'body'])->getContent();

        $redirected = $transport->getRequests()[1];
        $this->assertSame($redirectedMethod, $redirected->method);
        $this->assertSame($redirectedBody, $redirected->body);
    }

    public function testSensitiveHeadersNotSentToAnotherOrigin(): void
    {
        $transport = new MockTransport([
            new MockResponse('', 302, ['location' => '/same']),
            new MockResponse('', 302, ['location' => 'https://evil.example.org/']),
            new MockResponse(),
        ]);

        (new HttpClient(['auth_bearer' => 'secret', 'headers' => ['cookie' => 'a=1', 'x-trace' => 't']], $transport))->request('GET', 'https://example.com/')->getContent();

        [, $same, $other] = $transport->getRequests();
        $this->assertSame(['Bearer secret'], $same->headers['authorization']);
        $this->assertSame(['a=1'], $same->headers['cookie']);
        $this->assertSame(['x-trace' => ['t']], $other->headers);
    }

    public function testMaxRedirects(): void
    {
        $client = new HttpClient(['max_redirects' => 2], new MockTransport(static fn(Request $request): MockResponse => new MockResponse('', 302, ['location' => $request->url . 'x'])));

        $response = $client->request('GET', 'http://example.com/');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(2, $response->getInfo('redirect_count'));
        $this->assertSame('http://example.com/xxx', $response->getInfo('redirect_url'));

        $this->expectException(RedirectionException::class);

        $response->getContent();
    }

    public function testNoRedirect(): void
    {
        $response = (new HttpClient(['max_redirects' => 0], new MockTransport(new MockResponse('', 301, ['location' => '/x']))))->request('GET', 'http://example.com');

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('http://example.com/x', $response->getInfo('redirect_url'));
    }

    public function testRedirectionToAnotherProtocolRefused(): void
    {
        $response = (new HttpClient([], new MockTransport(new MockResponse('', 302, ['location' => 'file:///etc/passwd']))))->request('GET', 'http://example.com');

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Redirection of "http://example.com/" refused: Unsupported URL "file:///etc/passwd"');

        $response->getStatusCode();
    }

    public function testMockErrors(): void
    {
        $client = new HttpClient([], new MockTransport());

        try {
            $client->request('GET', 'http://example.com')->getStatusCode();
            $this->fail('No exception');
        } catch (TransportException $e) {
            $this->assertSame('No more mock response for GET "http://example.com/".', $e->getMessage());
        }

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('The mock callback must return a');

        (new HttpClient([], new MockTransport(static fn(): string => 'x')))->request('GET', 'http://example.com')->getStatusCode();
    }

    public function testInvalidMaxRedirects(): void
    {
        $this->expectException(HttpClientExceptionInterface::class);

        (new HttpClient())->request('GET', 'http://example.com', ['max_redirects' => -1]);
    }
}

<?php

declare(strict_types=1);

namespace Test\HttpClient;

use ArrayIterator;
use Neutrino\HttpClient\Exception\InvalidArgumentException;
use Neutrino\HttpClient\Options;
use Neutrino\HttpClient\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OptionsTest extends TestCase
{
    public function testMerge(): void
    {
        $options = Options::merge(Options::DEFAULTS, ['headers' => ['X-A' => 'a', 'X-B' => 'b'], 'query' => ['a' => 1], 'timeout' => 5]);
        $options = Options::merge($options, ['headers' => ['x-b' => 'B', 'x-c: c'], 'query' => ['b' => 2], 'timeout' => 3]);

        $this->assertSame(['x-b' => ['B'], 'x-c' => ['c'], 'x-a' => ['a']], $options['headers']);
        $this->assertSame(['b' => 2, 'a' => 1], $options['query']);
        $this->assertSame(3, $options['timeout']);
    }

    public function testUnknownOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown HTTP client option "base_url": use base_uri, query,');

        Options::merge(Options::DEFAULTS, ['base_url' => 'https://example.com']);
    }

    /**
     * RFC 3986, 5.4.
     *
     * @return iterable<array{string, string}>
     */
    public static function references(): iterable
    {
        yield ['g', 'http://a/b/c/g'];
        yield ['./g', 'http://a/b/c/g'];
        yield ['g/', 'http://a/b/c/g/'];
        yield ['/g', 'http://a/g'];
        yield ['//g', 'http://g/'];
        yield ['?y', 'http://a/b/c/d;p?y'];
        yield ['g?y', 'http://a/b/c/g?y'];
        yield ['#s', 'http://a/b/c/d;p?q'];
        yield ['', 'http://a/b/c/d;p?q'];
        yield ['.', 'http://a/b/c/'];
        yield ['..', 'http://a/b/'];
        yield ['../g', 'http://a/b/g'];
        yield ['../../g', 'http://a/g'];
        yield ['../../../g', 'http://a/g'];
        yield ['https://other:8443/x?y', 'https://other:8443/x?y'];
    }

    #[DataProvider('references')]
    public function testResolve(string $reference, string $expected): void
    {
        $this->assertSame($expected, Options::resolve($reference, 'http://a/b/c/d;p?q'));
    }

    /**
     * @return iterable<array{string, string|null}>
     */
    public static function refusedUrls(): iterable
    {
        yield ['file:///etc/passwd', null];
        yield ['gopher://127.0.0.1:6379/_INFO', null];
        yield ['dict://127.0.0.1:11211/stats', null];
        yield ['/relative', null];
        yield ['http:///no-host', null];
        yield ['/x', 'file:///etc/'];
    }

    #[DataProvider('refusedUrls')]
    public function testOnlyHttpUrls(string $url, ?string $base): void
    {
        $this->expectException(InvalidArgumentException::class);

        Options::prepare('GET', $url, ['base_uri' => $base]);
    }

    public function testPrepare(): void
    {
        $request = Options::prepare('post', '/users?sort=name#top', [
            'base_uri'     => 'https://api.example.com/v1/',
            'query'        => ['page' => 2, 'tags' => ['a b', 'c']],
            'headers'      => ['Accept' => ['application/json', 'text/plain']],
            'json'         => ['name' => 'Ada', 'score' => 1.0],
            'auth_bearer'  => 'token',
            'timeout'      => 2.5,
            'max_duration' => 10,
            'http_version' => '2.0',
        ]);

        $this->assertSame('POST', $request->method);
        $this->assertSame('https://api.example.com/users?sort=name&page=2&tags%5B0%5D=a%20b&tags%5B1%5D=c', $request->url);
        $this->assertSame('{"name":"Ada","score":1.0}', $request->body);
        $this->assertSame([
            'accept: application/json',
            'accept: text/plain',
            'content-type: application/json',
            'authorization: Bearer token',
        ], $request->headerLines());
        $this->assertSame(2.5, $request->timeout);
        $this->assertSame(10.0, $request->maxDuration);
        $this->assertSame('2.0', $request->httpVersion);
        $this->assertTrue($request->verifyPeer);
    }

    public function testDefaults(): void
    {
        $request = Options::prepare('GET', 'http://example.com', []);

        $this->assertSame('http://example.com/', $request->url);
        $this->assertSame('', $request->body);
        $this->assertSame([], $request->headers);
        $this->assertSame((float) ini_get('default_socket_timeout'), $request->timeout);
        $this->assertSame(0.0, $request->maxDuration);
        $this->assertSame('1.1', $request->httpVersion);
        $this->assertTrue($request->verifyPeer);
        $this->assertTrue($request->verifyHost);
    }

    /**
     * @return iterable<string, array{mixed, string, string|null}>
     */
    public static function bodies(): iterable
    {
        yield 'string' => ['raw', 'raw', null];
        yield 'form' => [['a' => 1, 'b' => 'x y'], 'a=1&b=x+y', 'application/x-www-form-urlencoded'];
        yield 'iterable' => [new ArrayIterator(['a', 'b']), 'ab', null];

        $resource = fopen('php://memory', 'w+');
        fwrite($resource, 'from a stream');
        rewind($resource);

        yield 'resource' => [$resource, 'from a stream', null];
    }

    #[DataProvider('bodies')]
    public function testBody(mixed $body, string $expected, ?string $contentType): void
    {
        $request = Options::prepare('POST', 'http://example.com', ['body' => $body]);

        $this->assertSame($expected, $request->body);
        $this->assertSame($contentType, $request->headers['content-type'][0] ?? null);
    }

    public function testContentTypeKept(): void
    {
        $request = Options::prepare('POST', 'http://example.com', ['json' => [], 'headers' => ['Content-Type' => 'application/vnd.api+json']]);

        $this->assertSame(['application/vnd.api+json'], $request->headers['content-type']);
    }

    public function testAuthBasic(): void
    {
        $this->assertSame(['Basic ' . base64_encode('user:pass')], Options::prepare('GET', 'http://e.com', ['auth_basic' => 'user:pass'])->headers['authorization']);
        $this->assertSame(['Basic ' . base64_encode('user:pa:ss')], Options::prepare('GET', 'http://e.com', ['auth_basic' => ['user', 'pa:ss']])->headers['authorization']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidOptions(): iterable
    {
        yield 'auth conflict' => [['auth_basic' => 'a:b', 'auth_bearer' => 't'], 'cannot be used together'];
        yield 'header injection' => [['headers' => ['x-a' => "a\r\nx-b: b"]], 'contains a line break'];
        yield 'json' => [['json' => NAN], 'Option "json"'];
        yield 'timeout' => [['timeout' => -1], 'Option "timeout"'];
        yield 'http_version' => [['http_version' => '3'], 'Option "http_version"'];
        yield 'body' => [['body' => 12], 'Option "body"'];
        yield 'query' => [['query' => 'a=1'], 'Option "query"'];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('invalidOptions')]
    public function testInvalidOptions(array $options, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        Options::prepare('GET', 'http://example.com', $options);
    }

    public function testNoProxy(): void
    {
        $request = Options::prepare('GET', 'http://api.internal.test/x', ['proxy' => 'http://proxy:3128', 'no_proxy' => 'localhost, internal.test']);

        $this->assertNull($request->proxy());
        $this->assertSame('http://proxy:3128', $request->with(['url' => 'http://example.com/'])->proxy());
        $this->assertNull(Options::prepare('GET', 'http://example.com', ['proxy' => 'http://proxy:3128', 'no_proxy' => ['*']])->proxy());
        $this->assertNull((new Request('GET', 'http://example.com/'))->proxy());
    }
}

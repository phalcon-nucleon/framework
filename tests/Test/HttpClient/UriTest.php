<?php

namespace Test\HttpClient;

use Neutrino\Debug\Reflexion;
use Neutrino\HttpClient\Uri;

class UriTest extends \PHPUnit_Framework_TestCase
{
    public function dataConstruct()
    {
        return [
            [[], null],
            [[], ''],
            [[
                'path' => '/'
            ], '/'],
            [[
                'path' => '/',
                'query' => ['q' => 's'],
            ], '/?q=s'],
            [[
                'path' => '/',
                'query' => ['q' => 'a'],
            ], '/?q=s&q=a'],
            [[
                'path' => '/',
                'query' => ['q' => ['s', 'a']],
            ], '/?q[]=s&q[]=a'],
            [[
                'scheme' => 'http',
                'host' => 'www.domain.com',
                'path' => '/',
                'query' => ['q' => ['s', 'a']],
            ], 'http://www.domain.com/?q[]=s&q[]=a'],
            [[
                'scheme' => 'http',
                'host' => 'www.domain.com',
                'port' => '8080',
                'path' => '/path',
                'query' => ['q' => ['s', 'a']],
            ], 'http://www.domain.com:8080/path?q[]=s&q[]=a'],
            [[
                'scheme' => 'http',
                'host' => 'www.domain.com',
                'user' => 'user',
                'pass' => 'pass',
                'path' => '/path',
                'query' => ['q' => ['s', 'a']],
            ], 'http://user:pass@www.domain.com/path?q[]=s&q[]=a'],
            [[
                'scheme' => 'http',
                'user' => 'user',
                'pass' => 'pass',
                'host' => 'www.domain.com',
                'port' => '8080',
                'path' => '/path',
                'fragment' => 'frag',
                'query' => ['q' => ['s', 'a']],
            ], 'http://user:pass@www.domain.com:8080/path?q[]=s&q[]=a#frag'],

            [[
                'path' => '/',
                'query' => ['q' => 's'],
            ], new Uri('/?q=s')],

            [[
                'path' => '/',
                'query' => ['q' => 's'],
            ], [
                'path' => '/',
                'query' => ['q' => 's'],
            ]],
        ];
    }

    /**
     * @dataProvider dataConstruct
     *
     * @param $expectedParts
     * @param $url
     */
    public function testBasic($expectedParts, $url)
    {
        $uri = new Uri($url);

        $parts = Reflexion::get($uri, 'parts');

        $this->assertEquals($expectedParts, $parts);

        foreach ($expectedParts as $key => $part) {
            $this->assertTrue(isset($uri->$key));
            $this->assertEquals($part, $uri->$key);

            $uri->$key = 'test';

            $parts = Reflexion::get($uri, 'parts');
            $this->assertEquals('test', $parts[$key]);
            $this->assertEquals('test', $uri->$key);

            unset($uri->$key);

            $parts = Reflexion::get($uri, 'parts');
            $this->assertArrayNotHasKey($key, $parts);
            $this->assertFalse(isset($uri->$key));
        }

        $uri->test = 'test';

        $parts = Reflexion::get($uri, 'parts');
        $this->assertArrayHasKey('test', $parts);
        $this->assertEquals('test', $parts['test']);
        $this->assertEquals('test', $uri->test);

        unset($uri->test);

        $parts = Reflexion::get($uri, 'parts');
        $this->assertArrayNotHasKey('test', $parts);
        $this->assertFalse(isset($uri->test));
    }

    public function dataBuild()
    {
        return [
            ['', null],
            ['', ''],
            ['/', '/'],
            ['/?q=s', '/?q=s'],
            ['/?q=a', '/?q=s&q=a'],
            ['/?q%5B0%5D=s&q%5B1%5D=a', '/?q[]=s&q[]=a'],
            ['http://www.domain.com/?q%5B0%5D=s&q%5B1%5D=a', 'http://www.domain.com/?q[]=s&q[]=a'],
            ['http://www.domain.com/path?q%5B0%5D=s&q%5B1%5D=a', 'http://www.domain.com/path?q[]=s&q[]=a'],
            ['http://user:pass@www.domain.com/path?q%5B0%5D=s&q%5B1%5D=a', 'http://user:pass@www.domain.com/path?q[]=s&q[]=a'],
            ['http://user:pass@www.domain.com:8080/path?q%5B0%5D=s&q%5B1%5D=a#frag', 'http://user:pass@www.domain.com:8080/path?q[]=s&q[]=a#frag'],

            ['/?q=s', new Uri('/?q=s')],

            ['/?q=s', [
                'path' => '/',
                'query' => ['q' => 's'],
            ]],
            ['/?q%5B0%5D=s&q%5B1%5D=a', [
                'path' => '/',
                'query' => ['q' => ['s', 'a']],
            ]],
            ['http://domain.com:8080/?q%5B0%5D=s&q%5B1%5D=a', [
                'scheme' => 'http',
                'host' => 'domain.com',
                'port' => '8080',
                'path' => '/',
                'query' => ['q' => ['s', 'a']],
            ]],
        ];
    }

    /**
     * @dataProvider dataBuild
     * @param $expectedUri
     * @param $uri
     */
    public function testBuild($expectedUri, $uri)
    {
        $uri = new Uri($uri);
        $this->assertEquals($expectedUri, $uri->build());
        $this->assertEquals($expectedUri, (string)$uri);
    }

    public function dataExtendQuery()
    {
        return [
            [[], '', null],
            [['q' => 's'], '?q=s', null],
            [['q' => 'q'], '?q[]=s&q[]=a', ['q' => 'q']],
            [['q' => ['q', 's']], '?q=s', ['q' => ['q', 's']]],
            [['q' => ['q', 's']], 'http://www.domain.com/?q=a', ['q' => ['q', 's']]],
        ];
    }

    /**
     * @dataProvider dataExtendQuery
     *
     * @param $expectedQuery
     * @param $uri
     * @param $query
     */
    public function testExtendQuery($expectedQuery, $uri, $query)
    {
        $uri = new Uri($uri);
        $uri->extendQuery($query);
        $this->assertEquals($expectedQuery, $uri->query);
    }

    public function dataExtendPath()
    {
        return [
            ['/', '/', null],
            ['/path', '', 'path'],
            ['/path', '/path', null],
            ['/query', '/path', '/query'],
            ['/path/query', '/path/path', 'query'],
            ['/foo/bar/last', 'http://phalconphp.com/foo/bar/baz?var1=a&var2=1', 'last'],
        ];
    }

    /**
     * @dataProvider dataExtendPath
     *
     * @param $expectedPath
     * @param $uri
     * @param $path
     */
    public function testExtendPath($expectedPath, $uri, $path)
    {
        $uri = new Uri($uri);
        $uri->extendPath($path);
        $this->assertEquals($expectedPath, $uri->path);
    }

    public function dataResolve()
    {
        return [
            ['/', '/', null],
            ['/path', '/path', null],
            ['/query', '/path', '/query'],
            ['/path/query', '/path/path', 'query'],
            ['http://domain.com/path', '/path', 'http://domain.com'],
            ['http://domain.com/path', 'http://domain.com', '/path'],
            ['http://domain.com/foo/bar/last?var1=a&var2=1', 'http://domain.com/foo/bar/baz?var1=a&var2=1', 'last'],
            ['http://sub.domain.com/foo/bar/baz?var1=a&var2=1', 'http://domain.com/foo/bar/baz?var1=a&var2=1', 'http://sub.domain.com'],
            ['http://domain.com/foo/bar/baz?var1=a&var2=1&q=1', 'http://domain.com/foo/bar/baz?var1=a&var2=1', '?q=1'],
        ];
    }

    /**
     * @dataProvider dataResolve
     *
     * @param $expectedUri
     * @param $uri
     * @param $resolve
     */
    public function testResolve($expectedUri, $uri, $resolve)
    {
        $uri = new Uri($uri);
        $newUri = $uri->resolve($resolve);
        $this->assertEquals($expectedUri, (string)$newUri);
    }
}

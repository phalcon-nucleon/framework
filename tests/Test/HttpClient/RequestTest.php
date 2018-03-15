<?php

namespace Test\HttpClient;

use Neutrino\Debug\Reflexion;
use Neutrino\Http\Standards\Method;
use Neutrino\HttpClient\Request;
use Neutrino\HttpClient\Response;
use Neutrino\HttpClient\Uri;
use PHPUnit\Framework\TestCase;

/**
 * Class RequestTest
 *
 * @package Test
 */
class RequestTest extends TestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Request
     */
    private function getRequest()
    {
        $request = $this->getMockForAbstractClass(Request::class);

        $request->expects($this->any())->method('buildParams')->willReturnSelf();
        $request->expects($this->any())->method('buildHeaders')->willReturnSelf();
        $request->expects($this->any())->method('buildProxy')->willReturnSelf();
        $request->expects($this->any())->method('buildCookies')->willReturnSelf();

        return $request;
    }

    public function testUri()
    {
        $request = $this->getRequest();

        $request->setUri('http://www.google.com/');

        $this->assertEquals(new Uri('http://www.google.com/'), $request->getUri());

        $request
            ->setUri('http://www.google.com/')
            ->setParams(['test' => 'test']);

        $this->assertEquals(new Uri('http://www.google.com/?test=test'), $request->getUri());

        $request
            ->setMethod(Method::POST)
            ->setUri('http://www.google.com/')
            ->setParams(['test' => 'test']);

        $this->assertEquals(new Uri('http://www.google.com/'), $request->getUri());
    }

    public function testParams()
    {
        $request = $this->getRequest();

        $request->setParams([
            'test' => 'value',
            'test1' => 'value1',
        ]);

        $this->assertEquals([
            'test' => 'value',
            'test1' => 'value1',
        ], $request->getParams());

        $request->setParam('test', 'test');

        $this->assertEquals([
            'test' => 'test',
            'test1' => 'value1',
        ], $request->getParams());

        $request->setParams([
            'test' => 'value',
            'test2' => 'value2',
            'test3' => 'value3',
        ], true);

        $this->assertEquals([
            'test' => 'value',
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $request->getParams());


        $request->setParams([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3',
        ]);

        $this->assertEquals([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $request->getParams());
    }

    public function testProxy()
    {
        $request = $this->getRequest();

        $request->setProxy('domain.com');

        $this->assertEquals([
            'host' => 'domain.com',
            'port' => 8080,
            'access' => null
        ], $request->getProxy());

        $request->setProxy('domain.com', 8888);

        $this->assertEquals([
            'host' => 'domain.com',
            'port' => 8888,
            'access' => null
        ], $request->getProxy());

        $request->setProxy('domain.com', 8888, 'user:pass');

        $this->assertEquals([
            'host' => 'domain.com',
            'port' => 8888,
            'access' => 'user:pass'
        ], $request->getProxy());
    }

    public function testOptions()
    {
        $request = $this->getRequest();

        $request->setOptions([
            'test' => 'value',
            'test1' => 'value1',
        ]);

        $this->assertEquals([
            'test' => 'value',
            'test1' => 'value1',
        ], $request->getOptions());

        $request->setOption('test', 'test');

        $this->assertEquals([
            'test' => 'test',
            'test1' => 'value1',
        ], $request->getOptions());

        $request->setOptions([
            'test' => 'value',
            'test2' => 'value2',
            'test3' => 'value3',
        ], true);

        $this->assertEquals([
            'test' => 'value',
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $request->getOptions());


        $request->setOptions([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3',
        ]);

        $this->assertEquals([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $request->getOptions());
    }

    public function testHeader()
    {
        $request = $this->getRequest();

        $header = Reflexion::get($request, 'header');

        $request->setHeaders([
            'test' => 'value',
            'test1' => 'value1',
        ]);

        $this->assertEquals([
            'test' => 'value',
            'test1' => 'value1',
        ], $header->getHeaders());

        $request->setHeader('test', 'test');

        $this->assertEquals([
            'test' => 'test',
            'test1' => 'value1',
        ], $header->getHeaders());

        $request->setHeaders([
            'test' => 'value',
            'test2' => 'value2',
            'test3' => 'value3',
        ], true);

        $this->assertEquals([
            'test' => 'value',
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $header->getHeaders());


        $request->setHeaders([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3',
        ]);

        $this->assertEquals([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $header->getHeaders());
    }

    public function testCookies()
    {
        $request = $this->getRequest();

        $request->setCookies([
            'test' => 'value',
            'test1' => 'value1',
        ]);

        $this->assertEquals([
            'test' => 'value',
            'test1' => 'value1',
        ], $request->getCookies());

        $request->setCookie('test', 'test');
        $request->setCookie(null, 'test');

        $this->assertEquals([
            0 => 'test',
            'test' => 'test',
            'test1' => 'value1',
        ], $request->getCookies());

        $request->setCookies([
            'test' => 'value',
            'test2' => 'value2',
            'test3' => 'value3',
        ], true);

        $this->assertEquals([
            0 => 'test',
            'test' => 'value',
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $request->getCookies());

        $request->setCookies([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3',
        ]);

        $this->assertEquals([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $request->getCookies());

        $this->assertEquals(implode(';', [
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ]), $request->getCookies(true));
    }

    public function testJson()
    {
        $request = $this->getRequest();

        $request->setJsonRequest(true);

        $this->assertTrue($request->isJsonRequest());

        $request->setJsonRequest(false);

        $this->assertFalse($request->isJsonRequest());
    }

    public function dataRequest()
    {
        return [
            [['uri'=>'/?q=q', 'headers' => ['Accept' => '*/*'], 'json' => false, 'full' => false,], Method::GET, '/', ['q' => 'q'], ['headers' => ['Accept' => '*/*']]],
            [['uri'=>'/?q=q', 'headers' => ['Accept' => '*/*'], 'json' => false, 'full' => false,], Method::HEAD, '/', ['q' => 'q'], ['headers' => ['Accept' => '*/*'], 'full' => null]],
            [['uri'=>'/?q=q', 'headers' => ['Accept' => '*/*'], 'json' => false, 'full' => true,], Method::DELETE, '/', ['q' => 'q'], ['headers' => ['Accept' => '*/*'], 'full' => true]],
            [['uri'=>'/', 'headers' => ['Accept' => '*/*'], 'json' => false, 'full' => false,], Method::POST, '/', ['q' => 'q'], ['headers' => ['Accept' => '*/*'], 'json' => null, 'full' => null]],
            [['uri'=>'/', 'headers' => ['Accept' => '*/*'], 'json' => false, 'full' => false,], Method::PUT, '/', ['q' => 'q'], ['headers' => ['Accept' => '*/*'], 'json' => false, 'full' => false]],
            [['uri'=>'/', 'headers' => ['Accept' => '*/*'], 'json' => true, 'full' => true,], Method::PATCH, '/', ['q' => 'q'], ['headers' => ['Accept' => '*/*'], 'json' => true, 'full' => true]],
        ];
    }

    /**
     * @dataProvider dataRequest
     */
    public function testRequest($expected, $method, $url, $params, $options)
    {
        $request = $this->getRequest();

        $this->assertEquals($request, $request->{strtolower($method)}($url, $params, $options));
        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($expected['uri'], $request->getUri()->build());
        $this->assertEquals($params, $request->getParams());
        $this->assertEquals($expected['headers'], $request->getHeaders());
        $this->assertEquals($expected['json'], $request->isJsonRequest());
        $this->assertEquals($expected['full'], $request->isFullResponse());

        $this->assertEquals($request, $request->request($method, $url, $params, $options));
        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($expected['uri'], $request->getUri()->build());
        $this->assertEquals($params, $request->getParams());
        $this->assertEquals($expected['headers'], $request->getHeaders());
        $this->assertEquals($expected['json'], $request->isJsonRequest());
        $this->assertEquals($expected['full'], $request->isFullResponse());

        $this->assertEquals($request, $request->request($method, $url));
        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($expected['uri'], $request->getUri()->build());
        $this->assertEquals($params, $request->getParams());
        $this->assertEquals($expected['headers'], $request->getHeaders());
        $this->assertEquals($expected['json'], $request->isJsonRequest());
        $this->assertEquals($expected['full'], $request->isFullResponse());
    }

    public function testSend()
    {
        $request = $this->getRequest();

        $request->expects($this->once())->method('makeCall')->willReturnCallback(function () use ($request) {
            return $request->getResponse();
        });

        $response = $request->send();

        $this->assertInstanceOf(Response::class, $response);
    }

}

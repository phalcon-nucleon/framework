<?php

namespace Test\HttpClient\Provider\Stream;

use Neutrino\Debug\Reflexion;
use Neutrino\HttpClient\Provider\StreamContext;
use Neutrino\Http\Standards\Method;
use Test\HttpClient\Provider\TraitWithLocalServer;

/**
 * Class StreamTest
 *
 * @package     Test\Provider\Stream
 */
class StreamContextTest extends \PHPUnit\Framework\TestCase
{
    use TraitWithLocalServer;

    public function dataCall()
    {
        return [
            "GET 200"    => self::makeDataCall(Method::GET, 200),
            "HEAD 200"   => self::makeDataCall(Method::HEAD, 200),
            "DELETE 200" => self::makeDataCall(Method::DELETE, 200),
            "POST 200"   => self::makeDataCall(Method::POST, 200),
            "PUT 200"    => self::makeDataCall(Method::PUT, 200),
            "PATCH 200"  => self::makeDataCall(Method::PATCH, 200),

            "GET 300" => self::makeDataCall(Method::GET, 300),
            "GET 400" => self::makeDataCall(Method::GET, 400),
            "GET 500" => self::makeDataCall(Method::GET, 500),
            "GET 600" => self::makeDataCall(Method::GET, 600),

            "GET 200'Success'" => self::makeDataCall(Method::GET, 200, 'Success'),

            "GET 200 query"    => self::makeDataCall(Method::GET, 200, null, ['query' => 'test']),
            "HEAD 200 query"   => self::makeDataCall(Method::HEAD, 200, null, ['query' => 'test']),
            "DELETE 200 query" => self::makeDataCall(Method::DELETE, 200, null, ['query' => 'test']),
            "POST 200 query"   => self::makeDataCall(Method::POST, 200, null, ['query' => 'test']),
            "PUT 200 query"    => self::makeDataCall(Method::PUT, 200, null, ['query' => 'test']),
            "PATCH 200 query"  => self::makeDataCall(Method::PATCH, 200, null, ['query' => 'test']),

            "GET 200 json"  => self::makeDataCall(Method::POST, 200, null, ['query' => 'test'], true),
            "POST 200 json" => self::makeDataCall(Method::POST, 200, null, ['query' => 'test'], true),
        ];
    }

    /**
     * @dataProvider dataCall
     *
     * @param       $expected
     * @param       $method
     * @param       $url
     * @param array $params
     * @param bool  $json
     */
    public function testCall($expected, $method, $url, $params = [], $json = false)
    {
        if ($method !== Method::HEAD) {
            $jsonBody = json_decode($expected['body'], true);

            $jsonBody['header_send']['Connection'] = 'close';

            ksort($jsonBody['header_send']);
            $expected['body'] = json_encode($jsonBody);
        }

        $streamCtx = new StreamContext();

        $streamCtx
            ->request($method, 'http://127.0.0.1:7999' . $url, $params)
            ->setJsonRequest($json)
            ->send();

        $response = $streamCtx->getResponse();

        $this->assertEquals($response->getCode(), $response->getHeader()->code);
        $this->assertEquals($expected['code'], $response->getCode());
        $this->assertEquals($expected['body'], $response->getBody());
        $this->assertEquals($expected['status'], $response->getHeader()->status);

        $header = $response->getHeader();
        foreach ($expected['headers'] as $name => $value) {
            $this->assertTrue($header->has($name));
            $this->assertEquals($value, $header->get($name));
        }
    }

    /**
     * @expectedException \Neutrino\HttpClient\Exception
     */
    public function testCallFailed()
    {
        try {
            $curl = new StreamContext();

            $curl
                ->setMethod('GET')
                ->setUri('http://invalid domain')
                ->setProxy('', null, null)// Force Remove proxy
                ->send();

        } catch (\Neutrino\HttpClient\Provider\Exception $e) {
            $this->assertFalse($e);
        } catch (\Neutrino\HttpClient\Exception $e) {
            $this->assertEquals(null, $curl->getResponse()->getCode());
            $this->assertEquals(null, $curl->getResponse()->getBody());
            $this->assertEquals(null, $curl->getResponse()->getData());
            $this->assertEquals($e->getMessage(), $curl->getResponse()->getError());
            $this->assertEquals($e->getCode(), $curl->getResponse()->getErrorCode());

            throw $e;
        }
    }

    public function testBuildProxy()
    {
        $streamCtx = new StreamContext;

        $streamCtx->setProxy('domain.com');

        Reflexion::invoke($streamCtx, 'buildProxy');

        $this->assertEquals('tcp://domain.com:8080', $streamCtx->getOptions()['proxy']);

        $streamCtx->setProxy('domain.com', 8888);

        Reflexion::invoke($streamCtx, 'buildProxy');

        $this->assertEquals('tcp://domain.com:8888', $streamCtx->getOptions()['proxy']);

        $streamCtx->setProxy('domain.com', 8888, 'user:pass');

        Reflexion::invoke($streamCtx, 'buildProxy');

        $this->assertEquals('tcp://user:pass@domain.com:8888', $streamCtx->getOptions()['proxy']);
    }

    public function testBuildCookies()
    {
        $streamCtx = new StreamContext;

        $streamCtx->setCookie(null, 'biscuit');
        $streamCtx->setCookie(null, 'muffin');

        Reflexion::invoke($streamCtx, 'buildCookies');

        $header = Reflexion::get($streamCtx, 'header');

        $this->assertTrue($header->has('Cookie'));
        $this->assertEquals(implode(';', ['biscuit', 'muffin']), $header->get('Cookie'));
    }

    public function testSetTimeout()
    {
        $streamCtx = new StreamContext();

        $streamCtx->setTimeout(10);

        $options = $streamCtx->getOptions();

        $this->assertArrayHasKey('timeout', $options);
        $this->assertEquals(10, $options['timeout']);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Neutrino\HttpClient\Provider\StreamContext\Streaming only support stream:start, stream:progress, stream:finish
     */
    public function testTryRegisterWrongEvent()
    {
        $streamCtx = new StreamContext\Streaming();

        $streamCtx->on('test', function () {
        });
    }

    public function testOnOff()
    {
        $streamCtx = new StreamContext\Streaming();

        $watcher = [];

        $closureStart = function () use (&$watcher) {
            $watcher[] = 'start';
        };
        $closureProgress = function () use (&$watcher) {
            $watcher[] = 'progress';
        };
        $closureFinish = function () use (&$watcher) {
            $watcher[] = 'finish';
        };

        $streamCtx->on($streamCtx::EVENT_START, $closureStart);
        $streamCtx->on($streamCtx::EVENT_PROGRESS, $closureProgress);
        $streamCtx->on($streamCtx::EVENT_FINISH, $closureFinish);

        $emitter = $streamCtx->getEventsManager();
        $listener = Reflexion::get($emitter, '_events');

        $this->assertArrayHasKey($streamCtx::EVENT_START, $listener);
        $this->assertArrayHasKey($streamCtx::EVENT_PROGRESS, $listener);
        $this->assertArrayHasKey($streamCtx::EVENT_FINISH, $listener);

        $this->assertEquals([$closureStart], $listener[$streamCtx::EVENT_START]);
        $this->assertEquals([$closureProgress], $listener[$streamCtx::EVENT_PROGRESS]);
        $this->assertEquals([$closureFinish], $listener[$streamCtx::EVENT_FINISH]);

        $streamCtx->off($streamCtx::EVENT_START, $closureStart);
        $listener = Reflexion::get($emitter, '_events');

        $this->assertEquals([], $listener[$streamCtx::EVENT_START]);

        $streamCtx->off($streamCtx::EVENT_PROGRESS, $closureProgress);
        $listener = Reflexion::get($emitter, '_events');

        $this->assertEquals([], $listener[$streamCtx::EVENT_PROGRESS]);

        $streamCtx->off($streamCtx::EVENT_FINISH, $closureFinish);
        $listener = Reflexion::get($emitter, '_events');

        $this->assertEquals([], $listener[$streamCtx::EVENT_FINISH]);
    }

    public function dataFullResponse()
    {
        $phpVersion = explode('-', PHP_VERSION)[0];

        return [
            'GET nr'  => [Method::GET, '/', false, '{"header_send":{"Connection":"close","Host":"127.0.0.1:7999"},"query":[]}'],
            'GET fr'  => [Method::GET, '/', true, implode("\r\n", [
                'HTTP/1.1 200 OK',
                'Host: 127.0.0.1:7999',
                'Connection: close',
                'X-Powered-By: PHP/' . $phpVersion,
                'Status-Code: 200 OK',
                'Request-Method: GET',
                'Content-type: text/html; charset=UTF-8',
                '',
                '{"header_send":{"Connection":"close","Host":"127.0.0.1:7999"},"query":[]}',
            ])],
            'POST nr' => [Method::POST, '/', false, '{"header_send":{"Connection":"close","Host":"127.0.0.1:7999"},"query":[]}'],
            'POST fr' => [Method::POST, '/', true, implode("\r\n", [
                'HTTP/1.1 200 OK',
                'Host: 127.0.0.1:7999',
                'Connection: close',
                'X-Powered-By: PHP/' . $phpVersion,
                'Status-Code: 200 OK',
                'Request-Method: POST',
                'Content-type: text/html; charset=UTF-8',
                '',
                '{"header_send":{"Connection":"close","Host":"127.0.0.1:7999"},"query":[]}',
            ])],
        ];
    }

    /**
     * @dataProvider dataFullResponse
     *
     * @param $method
     * @param $url
     * @param $fullResponse
     */
    public function testFullResponse($method, $url, $fullResponse, $expected)
    {
        $streamContext = new StreamContext();

        $response = $streamContext
            ->request($method, 'http://127.0.0.1:7999' . $url, [], ['full' => $fullResponse])
            ->send();

        $body = $response->getBody();

        if (PHP_VERSION_ID > 70100 && $fullResponse) {
            $body = preg_replace('/Date: .+\r\n/', '', $body);
        }

        $this->assertEquals($expected, $body);
    }

    /**
     * @expectedException \Neutrino\HttpClient\Provider\Exception
     * @expectedExceptionMessage Neutrino\HttpClient\Provider\StreamContext HTTP or HTTPS stream wrappers not registered.
     */
    public function testAvailabilityFail()
    {
        Reflexion::set(StreamContext::class, 'isAvailable', false);

        new StreamContext;
    }
}

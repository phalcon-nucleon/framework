<?php

namespace Test\HttpClient\Provider\Stream;

use Neutrino\Debug\Reflexion;
use Neutrino\HttpClient\Provider\Curl;
use Neutrino\Http\Standards\Method;
use Test\HttpClient\Provider\TraitWithLocalServer;

class CurlTest extends \PHPUnit\Framework\TestCase
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
     * @param $expected
     * @param $method
     * @param $url
     * @param $params
     */
    public function testCall($expected, $method, $url, $params = [], $json = false)
    {
        if ($method !== Method::HEAD) {
            $jsonBody = json_decode($expected['body'], true);

            $jsonBody['header_send']['Accept'] = '*/*';
            ksort($jsonBody['header_send']);
            $expected['body'] = json_encode($jsonBody);
        }

        $curl = new Curl();

        $curl
            ->request($method, 'http://127.0.0.1:7999' . $url, $params)
            ->setJsonRequest($json)
            ->setProxy('', null, null)// Force Remove proxy
            ->send();

        $response = $curl->getResponse();

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
            $curl = new Curl();

            $curl
                ->request(Method::GET, 'http://invalid domain')
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
        $curl = new Curl;

        $curl->setProxy('domain.com');

        Reflexion::invoke($curl, 'buildProxy');

        $options = $curl->getOptions();
        $this->assertArrayHasKey(CURLOPT_PROXY, $options);
        $this->assertArrayHasKey(CURLOPT_PROXYPORT, $options);
        $this->assertArrayNotHasKey(CURLOPT_PROXYUSERPWD, $options);
        $this->assertEquals('domain.com', $options[CURLOPT_PROXY]);
        $this->assertEquals(8080, $options[CURLOPT_PROXYPORT]);

        $curl->setProxy('domain.com', 8888);

        Reflexion::invoke($curl, 'buildProxy');

        $options = $curl->getOptions();
        $this->assertArrayHasKey(CURLOPT_PROXY, $options);
        $this->assertArrayHasKey(CURLOPT_PROXYPORT, $options);
        $this->assertArrayNotHasKey(CURLOPT_PROXYUSERPWD, $options);
        $this->assertEquals('domain.com', $options[CURLOPT_PROXY]);
        $this->assertEquals(8888, $options[CURLOPT_PROXYPORT]);

        $curl->setProxy('domain.com', 8888, 'user:pass');

        Reflexion::invoke($curl, 'buildProxy');

        $options = $curl->getOptions();
        $this->assertArrayHasKey(CURLOPT_PROXY, $options);
        $this->assertArrayHasKey(CURLOPT_PROXYPORT, $options);
        $this->assertArrayHasKey(CURLOPT_PROXYUSERPWD, $options);
        $this->assertEquals('domain.com', $options[CURLOPT_PROXY]);
        $this->assertEquals(8888, $options[CURLOPT_PROXYPORT]);
        $this->assertEquals('user:pass', $options[CURLOPT_PROXYUSERPWD]);
    }

    public function testBuildCookies()
    {
        $curl = new Curl;

        $curl->setCookie(null, 'biscuit');
        $curl->setCookie(null, 'muffin');

        Reflexion::invoke($curl, 'buildCookies');

        $options = $curl->getOptions();
        $this->assertArrayHasKey(CURLOPT_COOKIE, $options);
        $this->assertEquals(implode(';', ['biscuit', 'muffin']), $options[CURLOPT_COOKIE]);
    }

    public function testSetTimeout()
    {
        $curl = new Curl();

        $curl->setTimeout(10);

        $options = $curl->getOptions();

        $this->assertArrayHasKey(CURLOPT_TIMEOUT, $options);
        $this->assertEquals(10, $options[CURLOPT_TIMEOUT]);
    }

    public function testSetConnectTimeout()
    {
        $curl = new Curl();

        $curl->setConnectTimeout(10);

        $options = $curl->getOptions();

        $this->assertArrayHasKey(CURLOPT_CONNECTTIMEOUT, $options);
        $this->assertEquals(10, $options[CURLOPT_CONNECTTIMEOUT]);
    }


    public function testOnOff()
    {
        $curl = new Curl\Streaming();

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

        $curl->on($curl::EVENT_START, $closureStart);
        $curl->on($curl::EVENT_PROGRESS, $closureProgress);
        $curl->on($curl::EVENT_FINISH, $closureFinish);

        $emitter = $curl->getEventsManager();

        $listener = Reflexion::get($emitter, '_events');

        $this->assertArrayHasKey($curl::EVENT_START, $listener);
        $this->assertArrayHasKey($curl::EVENT_PROGRESS, $listener);
        $this->assertArrayHasKey($curl::EVENT_FINISH, $listener);

        $this->assertEquals([$closureStart], $listener[$curl::EVENT_START]);
        $this->assertEquals([$closureProgress], $listener[$curl::EVENT_PROGRESS]);
        $this->assertEquals([$closureFinish], $listener[$curl::EVENT_FINISH]);

        $curl->off($curl::EVENT_START, $closureStart);
        $listener = Reflexion::get($emitter, '_events');

        $this->assertEquals([], $listener[$curl::EVENT_START]);

        $curl->off($curl::EVENT_PROGRESS, $closureProgress);
        $listener = Reflexion::get($emitter, '_events');

        $this->assertEquals([], $listener[$curl::EVENT_PROGRESS]);

        $curl->off($curl::EVENT_FINISH, $closureFinish);
        $listener = Reflexion::get($emitter, '_events');

        $this->assertEquals([], $listener[$curl::EVENT_FINISH]);
    }

    public function dataFullResponse()
    {
        $phpVersion = explode('-', PHP_VERSION)[0];

        return [
            'GET nr'  => [Method::GET, '/', false, '{"header_send":{"Accept":"*\/*","Host":"127.0.0.1:7999"},"query":[]}'],
            'GET fr'  => [Method::GET, '/', true, implode("\r\n", [
                'HTTP/1.1 200 OK',
                'Host: 127.0.0.1:7999',
                'Connection: close',
                'X-Powered-By: PHP/' . $phpVersion,
                'Status-Code: 200 OK',
                'Request-Method: GET',
                'Content-type: text/html; charset=UTF-8',
                '',
                '{"header_send":{"Accept":"*\/*","Host":"127.0.0.1:7999"},"query":[]}',
            ])],
            'POST nr' => [Method::POST, '/', false, '{"header_send":{"Accept":"*\/*","Host":"127.0.0.1:7999"},"query":[]}'],
            'POST fr' => [Method::POST, '/', true, implode("\r\n", [
                'HTTP/1.1 200 OK' ,
                'Host: 127.0.0.1:7999',
                'Connection: close',
                'X-Powered-By: PHP/' . $phpVersion,
                'Status-Code: 200 OK',
                'Request-Method: POST',
                'Content-type: text/html; charset=UTF-8',
                '',
                '{"header_send":{"Accept":"*\/*","Host":"127.0.0.1:7999"},"query":[]}',
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
        $curl = new Curl();

        $response = $curl
            ->request($method, 'http://127.0.0.1:7999' . $url, [], ['full' => $fullResponse])
            ->setProxy('', null, null)// Force Remove proxy
            ->send();

        $body = $response->getBody();

        if (PHP_VERSION_ID > 70100 && $fullResponse) {
            $body = preg_replace('/Date: .+\r\n/', '', $body);
        }

        $this->assertEquals($expected, $body);
    }

    /**
     * @expectedException \Neutrino\HttpClient\Provider\Exception
     * @expectedExceptionMessage Neutrino\HttpClient\Provider\Curl require curl extension.
     */
    public function testAvailabilityFail()
    {
        Reflexion::set(Curl::class, 'isAvailable', false);

        new Curl;
    }
}
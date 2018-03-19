<?php

namespace Test\HttpClient;

use Neutrino\HttpClient\Header;

class HeaderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetSetHasRemoveCount()
    {
        $header = new Header();

        $this->assertEquals($header, $header->set('test', 'test'));

        $header->set('test', 'value');
        $header->set('test_1', 'value_1');

        $this->assertEquals(['test' => 'value', 'test_1' => 'value_1'], $header->getHeaders());

        $this->assertTrue($header->has('test'));
        $this->assertTrue($header->has('test_1'));
        $this->assertFalse($header->has('test_2'));

        $this->assertEquals('value', $header->get('test'));
        $this->assertEquals('value_1', $header->get('test_1'));
        $this->assertEquals(null, $header->get('test_2'));
        $this->assertEquals('value', $header->get('test', 'default'));
        $this->assertEquals('default', $header->get('test_2', 'default'));

        $this->assertEquals(2, $header->count());
        $this->assertCount(2, $header);

        $this->assertEquals($header, $header->remove('test'));

        $this->assertEquals(null, $header->get('test'));

        $this->assertEquals(1, $header->count());
        $this->assertCount(1, $header);

        $this->assertEquals($header, $header->setHeaders([
            'test'   => 'value',
            'test_2' => 'value_2'
        ], true));

        $this->assertEquals('value_2', $header->get('test_2', 'default'));

        $this->assertEquals(3, $header->count());
        $this->assertCount(3, $header);

        $this->assertEquals($header, $header->setHeaders([
            'test'   => 'value',
            'test_2' => 'value_2'
        ]));

        $this->assertEquals(2, $header->count());
        $this->assertCount(2, $header);
    }

    public function testBuild()
    {
        $header = new Header();

        $header->set('test', 'test');
        $header->set('test', 'value');
        $header->set('test_1', 'value_1');

        $this->assertEquals([
            'test: value',
            'test_1: value_1',
        ], $header->build());
    }

    public function dataParse()
    {
        return [
            [null, null, null, [], ""],
            [null, null, null, ['Date' => 'Thu, 27 Apr 2017 13:42:19 GMT'], PHP_EOL . "Date: Thu, 27 Apr 2017 13:42:19 GMT" . PHP_EOL],
            ['1.1', 200, 'OK', [], "HTTP/1.1 200 OK"],
            ['1.1', 200, 'Success', [], "HTTP/1.1 200 Success"],
            ['1.1', 302, 'Redirect', [], "HTTP/1.1 302 Redirect"],
            ['1.1', 418, 'I\'m a teapot', [], "HTTP/1.1 418 I'm a teapot"],
            ['1.1', 526, 'Whoops', [], "HTTP/1.1 526 Whoops"],
            [null, null, null, [
                'Date'           => 'Thu, 27 Apr 2017 13:42:19 GMT',
                'X-Powered-By'   => 'PHP/7.0.10',
                'Content-Length' => '5524',
                'Server'         => 'Apache/2.4.23 (Win64) PHP/7.0.10',
            ], [
                 "Date: Thu, 27 Apr 2017 13:42:19 GMT",
                 "X-Powered-By: PHP/7.0.10\r\n Content-Length: 5524",
                 "Server: Apache/2.4.23 (Win64) PHP/7.0.10",
             ]],
            ['1.1', 200, 'OK', [
                'Date'           => 'Thu, 27 Apr 2017 13:42:19 GMT',
                'Server'         => 'Apache/2.4.23 (Win64) PHP/7.0.10',
                'X-Powered-By'   => 'PHP/7.0.10',
                'Content-Length' => '5524',
                'Content-Type'   => 'text/html; charset=UTF-8',
            ], implode("\r\n", [
                "HTTP/1.1 200 OK",
                'Date: Thu, 27 Apr 2017 13:42:19 GMT',
                'Server: Apache/2.4.23 (Win64) PHP/7.0.10',
                'X-Powered-By: PHP/7.0.10',
                'Content-Length: 5524',
                'Content-Type: text/html; charset=UTF-8'
            ]),
            ]
        ];
    }

    /**
     * @dataProvider dataParse
     *
     * @param $expectedCode
     * @param $expectedStatus
     * @param $expectedHeaders
     * @param $raw
     */
    public function testParse($expectedVersion, $expectedCode, $expectedStatus, $expectedHeaders, $raw)
    {
        $header = new Header();

        if (is_array($raw)) {
            foreach ($raw as $item) {
                $header->parse($item);
            }
        } else {
            $header->parse($raw);
        }

        $this->assertEquals($expectedVersion, $header->version);
        $this->assertEquals($expectedCode, $header->code);
        $this->assertEquals($expectedStatus, $header->status);
        $this->assertEquals($expectedHeaders, $header->getHeaders());
    }
}

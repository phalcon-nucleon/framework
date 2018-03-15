<?php

namespace Test\HttpClient;

use Neutrino\HttpClient\Parser\Json;
use Neutrino\HttpClient\Parser\JsonArray;
use Neutrino\HttpClient\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class ResponseTest
 *
 * @package     Test
 */
class ResponseTest extends TestCase
{
    public function testIsser()
    {
        $response = new Response();

        $response->setCode(200);
        $this->assertTrue($response->isOk());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isFail());
        $this->assertFalse($response->isError());

        $response->setCode(300);
        $this->assertFalse($response->isOk());
        $this->assertTrue($response->isRedirect());
        $this->assertFalse($response->isFail());
        $this->assertFalse($response->isError());

        $response->setCode(400);
        $this->assertFalse($response->isOk());
        $this->assertFalse($response->isRedirect());
        $this->assertTrue($response->isFail());
        $this->assertFalse($response->isError());

        $response->setCode(500);
        $this->assertFalse($response->isOk());
        $this->assertFalse($response->isRedirect());
        $this->assertTrue($response->isFail());
        $this->assertFalse($response->isError());

        $response->setCode(600);
        $this->assertFalse($response->isOk());
        $this->assertFalse($response->isRedirect());
        $this->assertTrue($response->isFail());
        $this->assertFalse($response->isError());

        $response->setErrorCode(1);
        $this->assertFalse($response->isOk());
        $this->assertFalse($response->isRedirect());
        $this->assertTrue($response->isFail());
        $this->assertTrue($response->isError());
    }

    public function testParse()
    {
        $data = ['int' => 123, 'str' => 'abc', 'null' => null];

        $response = new Response();

        $response->setBody(json_encode($data));

        $this->assertEquals(null, $response->getData());

        $response->parse(Json::class);

        $this->assertEquals((object)$data, $response->getData());

        $response->parse(JsonArray::class);

        $this->assertEquals($data, $response->getData());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Neutrino\HttpClient\Response::parse: $parserize must implement Neutrino\HttpClient\Contract\Parser\Parserize
     */
    public function testParseException()
    {
        $response = new Response();

        $response->parse([]);
    }
}

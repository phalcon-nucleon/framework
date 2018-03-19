<?php

namespace Test\HttpClient;

use Neutrino\Debug\Reflexion;
use Neutrino\HttpClient\Factory;
use Neutrino\HttpClient\Provider\Curl;
use Neutrino\HttpClient\Provider\StreamContext;
use Test\TestCase\TestCase;

class FactoryTest extends TestCase
{
    public static function tearDownAfterClass()
    {
        Reflexion::set(Curl::class, 'isAvailable', null);
        Reflexion::set(StreamContext::class, 'isAvailable', null);

        parent::tearDownAfterClass();
    }

    public function testCurlAvailable()
    {
        Reflexion::set(Curl::class, 'isAvailable', true);

        $this->assertInstanceOf(Curl::class, Factory::makeRequest());
    }
    public function testStreamContextAvailable()
    {
        Reflexion::set(Curl::class, 'isAvailable', false);
        Reflexion::set(StreamContext::class, 'isAvailable', true);

        $this->assertInstanceOf(StreamContext::class, Factory::makeRequest());
    }

    /**
     * @expectedException \Neutrino\HttpClient\Exception
     * @expectedExceptionMessage No provider available
     */
    public function testNoAvailable()
    {
        Reflexion::set(Curl::class, 'isAvailable', false);
        Reflexion::set(StreamContext::class, 'isAvailable', false);

        Factory::makeRequest();
    }
}

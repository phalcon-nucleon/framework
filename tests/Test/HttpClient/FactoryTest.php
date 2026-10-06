<?php

namespace Test\HttpClient;

use Neutrino\Support\Reflection;
use Neutrino\HttpClient\Factory;
use Neutrino\HttpClient\Provider\Curl;
use Neutrino\HttpClient\Provider\StreamContext;
use Test\TestCase\TestCase;

class FactoryTest extends TestCase
{
    public static function tearDownAfterClass()
    {
        Reflection::set(Curl::class, 'isAvailable', null);
        Reflection::set(StreamContext::class, 'isAvailable', null);

        parent::tearDownAfterClass();
    }

    public function testCurlAvailable()
    {
        Reflection::set(Curl::class, 'isAvailable', true);

        $this->assertInstanceOf(Curl::class, Factory::makeRequest());
    }
    public function testStreamContextAvailable()
    {
        Reflection::set(Curl::class, 'isAvailable', false);
        Reflection::set(StreamContext::class, 'isAvailable', true);

        $this->assertInstanceOf(StreamContext::class, Factory::makeRequest());
    }

    /**
     * @expectedException \Neutrino\HttpClient\Exception
     * @expectedExceptionMessage No provider available
     */
    public function testNoAvailable()
    {
        Reflection::set(Curl::class, 'isAvailable', false);
        Reflection::set(StreamContext::class, 'isAvailable', false);

        Factory::makeRequest();
    }
}

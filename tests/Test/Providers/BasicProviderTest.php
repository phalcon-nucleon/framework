<?php

namespace Test\Providers;

use Neutrino\Support\SimpleProvider;
use Test\TestCase\TestCase;

class BasicProviderTest extends TestCase
{

    /**
     * @expectedException \RuntimeException
     */
    public function testNoName()
    {
        new StubWrongNameSimpleProvider;
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testNoClass()
    {
        new StubWrongClassSimpleProvider;
    }

    public function testRegister()
    {
        $provider = new StubRegisterSimpleProvider;

        $provider->registering();

        $this->assertTrue($this->getDI()->has('test'));
        $this->assertInstanceOf(StubRegisterSimpleProvider::class, $this->getDI()->get('test'));
    }
}

class StubWrongNameSimpleProvider extends SimpleProvider
{
};

class StubWrongClassSimpleProvider extends SimpleProvider
{
    protected $name = 'test';
};
class StubRegisterSimpleProvider extends SimpleProvider
{
    protected $name = 'test';
    protected $class = StubRegisterSimpleProvider::class;
};

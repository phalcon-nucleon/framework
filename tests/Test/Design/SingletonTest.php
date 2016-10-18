<?php

namespace Test\Design;

use Test\Stub\StubSingleton;
use Test\TestCase\TestCase;

class SingletonTest extends TestCase
{
    public function testBasic()
    {
        $this->assertInstanceOf(StubSingleton::class, StubSingleton::instance());
        $this->assertEquals('test', StubSingleton::instance()->getVar());
    }

    /**
     * @expectedException \Error
     */
    public function testFailConstruct()
    {
        new StubSingleton;
    }

    /**
     * @expectedException \Error
     */
    public function testFailClone()
    {
        $instance = StubSingleton::instance();

        $new_instance = clone $instance;
    }
    /**
     * @expectedException \RuntimeException
     */
    public function testFailCallClone()
    {
        $instance = StubSingleton::instance();

        $this->invokeMethod($instance, '__clone', []);
    }
}

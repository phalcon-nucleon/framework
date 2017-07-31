<?php

namespace Test\Design;

use Fake\Core\StubSingleton;
use Neutrino\Support\Reflacker;
use Test\TestCase\TestCase;

class SingletonTest extends TestCase
{
    public function testBasic()
    {
        $this->assertInstanceOf(StubSingleton::class, StubSingleton::instance());
        $this->assertEquals('test', StubSingleton::instance()->getVar());
    }

    public function testFailConstruct()
    {
        if (PHP_MAJOR_VERSION == 5) {
            $this->markTestSkipped('Can\'t test this in php5.');

            return;
        }

        $this->setExpectedException('\Error');

        new StubSingleton;
    }

    public function testFailClone()
    {
        if (PHP_MAJOR_VERSION == 5) {
            $this->markTestSkipped('Can\'t test this in php5.');

            return;
        }

        $this->setExpectedException('\Error');

        $instance = StubSingleton::instance();

        $new_instance = clone $instance;
    }

    public function testFailCallClone()
    {
        $this->setExpectedException('\RuntimeException');

        $instance = StubSingleton::instance();

        Reflacker::invoke($instance, '__clone');
    }
}

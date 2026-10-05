<?php

declare(strict_types=1);

namespace Test\Design;

use Error;
use Fake\Core\StubSingleton;
use Neutrino\Support\DesignPatterns\Singleton;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

final class SingletonTest extends TestCase
{
    public function testBasic(): void
    {
        $this->assertInstanceOf(StubSingleton::class, StubSingleton::instance());
        $this->assertSame(StubSingleton::instance(), StubSingleton::instance());
        $this->assertSame('test', StubSingleton::instance()->getVar());
    }

    public function testOneInstancePerSubclass(): void
    {
        $this->assertInstanceOf(OtherSingleton::class, OtherSingleton::instance());
        $this->assertInstanceOf(StubSingleton::class, StubSingleton::instance());
        $this->assertNotSame(OtherSingleton::instance(), StubSingleton::instance());
    }

    public function testFailConstruct(): void
    {
        $this->expectException(Error::class);

        new StubSingleton(); // @phpstan-ignore new.private
    }

    public function testFailClone(): void
    {
        $this->expectException(Error::class);

        clone StubSingleton::instance(); // @phpstan-ignore clone.private
    }

    public function testFailCallClone(): void
    {
        $this->expectException(RuntimeException::class);

        (new ReflectionMethod(Singleton::class, '__clone'))->invoke(StubSingleton::instance());
    }
}

class OtherSingleton extends Singleton {}

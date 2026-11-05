<?php

declare(strict_types=1);

namespace Test\Support;

use Neutrino\Support\Traits\InjectionAwareTrait;
use Phalcon\Di\Di;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Router;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

final class InjectionAwareTraitTest extends TestCase
{
    protected function tearDown(): void
    {
        Di::reset();
    }

    public function testMagicGetResolvesSharedServicesOnce(): void
    {
        $di = new FactoryDefault();
        $di->set('counter', fn() => new stdClass());

        $aware = new InjectionAwareStub();
        $aware->setDI($di);

        $this->assertInstanceOf(Router::class, $aware->router);
        $this->assertSame($aware->counter, $aware->counter);
        $this->assertSame($di->getShared('counter'), $aware->counter);
        $this->assertTrue(isset($aware->router));
        $this->assertFalse(isset($aware->unknown));
    }

    public function testGetDiFallsBackOnTheDefaultContainer(): void
    {
        $di = new FactoryDefault();
        Di::setDefault($di);

        $this->assertSame($di, (new InjectionAwareStub())->getDI());
    }

    public function testUnknownService(): void
    {
        $aware = new InjectionAwareStub();
        $aware->setDI(new Di());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unknown not found in dependency injection.');

        $aware->unknown;
    }
}

/**
 * @property-read mixed $router
 * @property-read mixed $counter
 * @property-read mixed $unknown
 */
class InjectionAwareStub
{
    use InjectionAwareTrait;
}

<?php

declare(strict_types=1);

namespace Test\Support;

use Mockery as m;
use Mockery\MockInterface;
use Neutrino\Support\Facades\Facade;
use Phalcon\Di\Di;
use Phalcon\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FacadeTest extends TestCase
{
    protected function setUp(): void
    {
        Facade::clearResolvedInstances();
    }

    protected function tearDown(): void
    {
        m::close();
        Facade::clearResolvedInstances();
        Di::reset();
    }

    public function testFacadeOverriderFacadeAccessor(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Facade does not implement getFacadeAccessor method.');

        WrongImplementFacadeStub::some();
    }

    public function testFacadeRootNotRegistered(): void
    {
        FacadeStub::setDependencyInjection(new FactoryDefault());

        $this->expectException(\Phalcon\Di\Exception::class);

        FacadeStub::bar();
    }

    public function testFacadeRootIsNotAnObject(): void
    {
        $di = new FactoryDefault();
        $di->setShared('foo', fn() => 'not an object');
        FacadeStub::setDependencyInjection($di);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A facade root has not been set.');

        FacadeStub::bar();
    }

    public function testFacadeOnObjectAccessor(): void
    {
        $this->assertSame('ObjectFacadeStub', ObjectFacadeStub::get());
        ObjectFacadeStub::set('foo/bar');
        $this->assertSame('ObjectFacadeStub', ObjectFacadeStub::get());
    }

    public function testFacadeOnSingletonObjectAccessor(): void
    {
        $this->assertSame('SingletonObjectFacadeStub', SingletonObjectFacadeStub::get());
        SingletonObjectFacadeStub::set('foo/bar');
        $this->assertSame('foo/bar', SingletonObjectFacadeStub::get());
    }

    public function testFacadeSwap(): void
    {
        $di = new FactoryDefault();
        $di->setShared('foo', new Foo());
        FacadeStub::setDependencyInjection($di);

        $this->assertSame('baz', FacadeStub::bar());

        FacadeStub::swap(new Bar());

        $this->assertSame('foo', FacadeStub::bar());
        $this->assertInstanceOf(Bar::class, $di->getShared('foo'));
    }

    public function testFacadeCallsUnderlyingService(): void
    {
        $di = new FactoryDefault();
        $di->setShared('foo', $mock = m::mock(Foo::class));
        $mock->shouldReceive('bar')->once()->andReturn('baz');
        FacadeStub::setDependencyInjection($di);

        $this->assertSame('baz', FacadeStub::bar());
        $this->assertSame($mock, FacadeStub::getFacadeRoot());
    }

    public function testShouldReceiveReturnsAMockeryMock(): void
    {
        $di = new FactoryDefault();
        $di->setShared('foo', new Foo());
        FacadeStub::setDependencyInjection($di);

        $mock = FacadeStub::shouldReceive('bar')->once()->with('x')->andReturn('mocked')->getMock();

        $this->assertInstanceOf(MockInterface::class, $mock);
        $this->assertInstanceOf(Foo::class, $mock);
        $this->assertSame('mocked', FacadeStub::bar('x'));
        $this->assertSame($mock, $di->getShared('foo'));
    }

    public function testShouldReceiveCanBeCalledTwice(): void
    {
        $di = new FactoryDefault();
        $di->setShared('foo', new \stdClass());
        FacadeStub::setDependencyInjection($di);

        $first = FacadeStub::shouldReceive('foo')->once()->with('bar')->andReturn('baz')->getMock();
        $second = FacadeStub::shouldReceive('foo2')->once()->with('bar2')->andReturn('baz2')->getMock();

        $this->assertSame($first, $second);
        $this->assertSame('baz', FacadeStub::foo('bar'));
        $this->assertSame('baz2', FacadeStub::foo2('bar2'));
    }

    public function testCanBeMockedWithoutUnderlyingInstance(): void
    {
        FacadeStub::setDependencyInjection(new Di());

        FacadeStub::shouldReceive('foo')->once()->andReturn('bar');

        $this->assertSame('bar', FacadeStub::foo());
    }

    public function testShouldReceiveWithoutMockery(): void
    {
        // A process whose autoloader only knows the framework: Mockery is not installed there.
        $src = var_export(dirname(__DIR__, 3) . '/src/Neutrino/', true);
        $code = <<<PHP
            spl_autoload_register(static function (string \$class): void {
                if (str_starts_with(\$class, 'Neutrino\\\\')) {
                    require $src . str_replace('\\\\', '/', substr(\$class, 9)) . '.php';
                }
            });
            class StubFacade extends Neutrino\\Support\\Facades\\Facade {
                protected static function getFacadeAccessor(): string { return 'foo'; }
            }
            try {
                StubFacade::shouldReceive('foo');
            } catch (LogicException \$e) {
                echo \$e->getMessage();
            }
            PHP;

        $process = proc_open([PHP_BINARY, '-r', $code], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($process);
        $output = (string) stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $this->assertSame('StubFacade::shouldReceive() requires mockery/mockery (composer require --dev mockery/mockery).', $output);
    }
}

class WrongImplementFacadeStub extends Facade {}

class FacadeStub extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'foo';
    }
}

class ObjectFacadeStub extends Facade
{
    protected static function getFacadeAccessor(): object
    {
        return new ValueHolder('ObjectFacadeStub');
    }
}

class SingletonObjectFacadeStub extends Facade
{
    private static ?ValueHolder $instance = null;

    protected static function getFacadeAccessor(): object
    {
        return self::$instance ??= new ValueHolder('SingletonObjectFacadeStub');
    }
}

class ValueHolder
{
    public function __construct(private string $value) {}

    public function get(): string
    {
        return $this->value;
    }

    public function set(string $value): void
    {
        $this->value = $value;
    }
}

class Foo
{
    public function bar(): string
    {
        return 'baz';
    }
}

class Bar
{
    public function bar(): string
    {
        return 'foo';
    }
}

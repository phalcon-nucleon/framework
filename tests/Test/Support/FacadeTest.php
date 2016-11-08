<?php
namespace Test\Support;

use Luxury\Support\Facades\Facade;
use Mockery as m;
use Phalcon\Di\FactoryDefault;
use Test\TestCase\TestCase;

/**
 * Class FacadeTest
 *
 * @package Support
 */
class FacadeTest extends TestCase
{
    public function setUp()
    {
        Facade::clearResolvedInstances();
        //FacadeStub::setDependencyInjection(null);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testFacadeOverriderFacadeAccessor()
    {
        $this->setExpectedExceptionRegExp(\RuntimeException::class,
            '/Facade does not implement getFacadeAccessor method\\./');

        WrongImplementFacadeStub::some();
    }

    public function testFacadeRootDiNotSet()
    {
        $this->setExpectedExceptionRegExp(\Phalcon\Di\Exception::class,
            "/Service '' wasn't found in the dependency injection container/");

        $app = new ApplicationStub;

        WrongRootFacadeStub::setDependencyInjection($app);
        WrongRootFacadeStub::some();
    }


    public function testFacadeWrongRoot()
    {
        $this->setExpectedExceptionRegExp(\RuntimeException::class,
            '/A facade root has not been set\\./');

        $app = new ApplicationStub;
        $app->setShared(null, function () {
            return null;
        });

        WrongRootFacadeStub::setDependencyInjection($app);
        WrongRootFacadeStub::some();
    }

    public function testFacadeMockWrongAccessor()
    {
        $app = new ApplicationStub;
        $app->setShared(null, function () {
            return null;
        });
        WrongRootFacadeStub::setDependencyInjection($app);

        WrongRootFacadeStub::shouldReceive('foo')->once()->andReturn(null);

        $this->assertNull(WrongRootFacadeStub::foo());
    }

    public function testFacadeOnAnonymousClass()
    {
        $this->assertEquals('AnonymousClassFacadeStub', AnonymousClassFacadeStub::get());
        AnonymousClassFacadeStub::set('foo/bar');
        $this->assertEquals('AnonymousClassFacadeStub', AnonymousClassFacadeStub::get());
    }

    public function testFacadeOnSingletonAnonymousClass()
    {
        $this->assertEquals('SingletonAnonymousClassFacadeStub', SingletonAnonymousClassFacadeStub::get());
        SingletonAnonymousClassFacadeStub::set('foo/bar');
        $this->assertEquals('foo/bar', SingletonAnonymousClassFacadeStub::get());
    }

    public function testFacadeSwap()
    {
        $app = new ApplicationStub;

        $app->setShared('foo', new Foo);
        FacadeStub::setDependencyInjection($app);

        $this->assertEquals('baz', FacadeStub::bar());

        FacadeStub::swap(new Bar);

        $this->assertEquals('foo', FacadeStub::bar());
    }

    public function testFacadeCallsUnderlyingApplication()
    {
        $app = new ApplicationStub;
        $app->setShared('foo', $mock = m::mock('StdClass'));
        $mock->shouldReceive('bar')->once()->andReturn('baz');
        FacadeStub::setDependencyInjection($app);
        $this->assertEquals('baz', FacadeStub::bar());
    }

    public function testShouldReceiveReturnsAMockeryMock()
    {
        $app = new ApplicationStub;
        $app->setShared('foo', new \stdClass);
        FacadeStub::setDependencyInjection($app);
        $this->assertInstanceOf('Mockery\MockInterface', $mock =
            FacadeStub::shouldReceive('foo')->once()->with('bar')->andReturn('baz')->getMock());
        $this->assertEquals('baz', FacadeStub::foo('bar'));
    }

    public function testShouldReceiveCanBeCalledTwice()
    {
        $app = new ApplicationStub;
        $app->setShared('foo', new \stdClass);
        FacadeStub::setDependencyInjection($app);
        $this->assertInstanceOf('Mockery\MockInterface', $mock =
            FacadeStub::shouldReceive('foo')->once()->with('bar')->andReturn('baz')->getMock());
        $this->assertInstanceOf('Mockery\MockInterface', $mock =
            FacadeStub::shouldReceive('foo2')->once()->with('bar2')->andReturn('baz2')->getMock());
        $this->assertEquals('baz', FacadeStub::foo('bar'));
        $this->assertEquals('baz2', FacadeStub::foo2('bar2'));
    }

    public function testCanBeMockedWithoutUnderlyingInstance()
    {
        FacadeStub::shouldReceive('foo')->once()->andReturn('bar');
        $this->assertEquals('bar', FacadeStub::foo());
    }
}

class WrongImplementFacadeStub extends Facade
{

}

class WrongRootFacadeStub extends Facade
{
    protected static function getFacadeAccessor()
    {
        return null;
    }
}

class FacadeStub extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'foo';
    }
}

class AnonymousClassFacadeStub extends Facade
{
    protected static function getFacadeAccessor()
    {
        return new AnonymousClassFacadeStubClass;
    }
}

class AnonymousClassFacadeStubClass
{
    private $value = 'AnonymousClassFacadeStub';

    public function get()
    {
        return $this->value;
    }

    public function set($value = null)
    {
        $this->value = $value;
    }
}

class SingletonAnonymousClassFacadeStub extends Facade
{
    private static $instance;

    private static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new SingletonAnonymousClassFacadeStubClass;
        }

        return self::$instance;
    }

    protected static function getFacadeAccessor()
    {
        return self::getInstance();
    }
}
class SingletonAnonymousClassFacadeStubClass
{
    private $value = 'SingletonAnonymousClassFacadeStub';

    public function get()
    {
        return $this->value;
    }

    public function set($value = null)
    {
        $this->value = $value;
    }
}
class ApplicationStub extends FactoryDefault
{
    protected $attributes = [];

    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    public function instance($key, $instance)
    {
        $this->attributes[$key] = $instance;
    }

    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet($key)
    {
        return $this->attributes[$key];
    }

    public function offsetSet($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function offsetUnset($key)
    {
        unset($this->attributes[$key]);
    }
}

class Foo
{
    public function bar()
    {
        return 'baz';
    }
}

class Bar
{
    public function bar()
    {
        return 'foo';
    }
}

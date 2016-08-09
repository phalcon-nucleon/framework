<?php
namespace Support;

use Luxury\Support\Facades\Facade;
use Mockery as m;
use Phalcon\Di\FactoryDefault;

/**
 * Class FacadeTest
 *
 * @package Support
 */
class FacadeTest extends \PHPUnit_Framework_TestCase
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

        FakeFacadeStub::some();
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

class FakeFacadeStub extends Facade
{

}

class FacadeStub extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'foo';
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
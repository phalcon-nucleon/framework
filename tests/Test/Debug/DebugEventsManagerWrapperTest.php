<?php

namespace Test\Debug;

use Neutrino\Foundation\Debug\DebugEventsManagerWrapper;
use Neutrino\Debug\Reflexion;
use Phalcon\Events\ManagerInterface;
use PHPUnit\Framework\TestCase;

class DebugEventsManagerWrapperTest extends TestCase
{
    public function tearDown()
    {
        Reflexion::set(DebugEventsManagerWrapper::class, 'events', []);

        parent::tearDown();
    }

    public function testProxy()
    {
        $stub = new StubEventsManager();

        $debug = new DebugEventsManagerWrapper($stub);

        $rmethods = Reflexion::getReflectionClass($debug)->getMethods(\ReflectionMethod::IS_PUBLIC);

        $methods = [];
        foreach ($rmethods as $idx => $method) {
            if ($method->isConstructor()
              || $method->isStatic()
              || $method->getName() === '__call'
              || $method->getName() === 'fireQueue') {
                unset($methods[$idx]);
                continue;
            }
            $methods[] = $method->getName();
        }

        $methods[] = 'custom';

        foreach ($methods as $method) {
            $debug->{$method}('arg1:arg1', 'arg3', 'arg3', 'arg4');
        }

        $watched = $stub->watched;

        $this->assertCount(count($methods), $watched);
        foreach ($methods as $idx => $method) {
            $this->assertEquals($method, $watched[$idx]['method']);
            $this->assertEquals(['arg1:arg1', 'arg3', 'arg3', 'arg4'], $watched[$idx]['args']);
        }
    }

    public function testEventsCatch()
    {
        $debug = new DebugEventsManagerWrapper(new StubEventsManager());
        $debug1 = new DebugEventsManagerWrapper(new StubEventsManager());

        $debug->fire('event:type', $debug, []);
        $debug1->fire('event:type', $debug1, []);

        $events = DebugEventsManagerWrapper::getEvents();

        $this->assertCount(2, $events);

        $keys = ['space', 'type', 'src', 'data' , 'raw_data', 'mt',];

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $events[0]);
            $this->assertArrayHasKey($key, $events[1]);
        }
    }

    public function testVerboseType(){

        $debug = new DebugEventsManagerWrapper(new StubEventsManager());

        $this->assertEquals('null', Reflexion::invoke($debug, '__verboseType', null));
        $this->assertEquals('true', Reflexion::invoke($debug, '__verboseType', true));
        $this->assertEquals('1', Reflexion::invoke($debug, '__verboseType', 1));
        $this->assertEquals('1.234', Reflexion::invoke($debug, '__verboseType', 1.234));
        $this->assertEquals("'abc'", Reflexion::invoke($debug, '__verboseType', 'abc'));
        $this->assertEquals("'".str_repeat('a', 30)."...'[50]", Reflexion::invoke($debug, '__verboseType', str_repeat('a', 50)));
        $this->assertEquals('object(DebugEventsManagerWrapper)', Reflexion::invoke($debug, '__verboseType', $debug));
        $this->assertEquals('array', Reflexion::invoke($debug, '__verboseType', []));

        $r = fopen('php://memory', 'a');
        $this->assertEquals('resource', Reflexion::invoke($debug, '__verboseType', $r));
        fclose($r);

        if(PHP_VERSION_ID >= 70200){
            $this->assertEquals('resource (closed)', Reflexion::invoke($debug, '__verboseType', $r));
        } else {
            $this->assertEquals('?', Reflexion::invoke($debug, '__verboseType', $r));
        }
    }
}

class StubEventsManager implements ManagerInterface
{
    public $watched;

    public function __call($name, $args)
    {
        $this->watched[] = ['method' => $name, 'args' => $args];
    }

    public function attach($eventType, $handler)
    {
        $this->watched[] = ['method' => __FUNCTION__, 'args' => func_get_args()];
    }

    public function detach($eventType, $handler)
    {
        $this->watched[] = ['method' => __FUNCTION__, 'args' => func_get_args()];
    }

    public function detachAll($type = null)
    {
        $this->watched[] = ['method' => __FUNCTION__, 'args' => func_get_args()];
    }

    public function fire($eventType, $source, $data = null)
    {
        $this->watched[] = ['method' => __FUNCTION__, 'args' => func_get_args()];

        return [];
    }

    public function getListeners($type)
    {
        $this->watched[] = ['method' => __FUNCTION__, 'args' => func_get_args()];

        return [];
    }
}

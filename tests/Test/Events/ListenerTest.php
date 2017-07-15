<?php
namespace Test;

use Fake\Core\Listeners\StubListener;
use Neutrino\Constants\Events;
use Neutrino\Events\Listener;
use Phalcon\Events\Manager;
use Test\TestCase\TestCase;

/**
 * Trait ListenerTest
 *
 * @package Test
 */
class ListenerTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     */
    public function testWrongAttach()
    {
        /** @var Listener $listener */
        $listener = new StubWrongListener;

        $listener->attach();
    }

    public function dataEvents()
    {
        return [
            ['space', [Events::APPLICATION]],
            ['space', [Events::DISPATCH]],
            ['listen', [Events\Http\Application::BEFORE_HANDLE => 'test']],
            ['listen', [Events\Dispatch::BEFORE_EXECUTE_ROUTE => 'test']],
        ];
    }

    /**
     * @dataProvider dataEvents
     */
    public function testAttach($property, $event)
    {
        $listener = new StubGoodListener;

        $mock = $this->createMock(Manager::class);
        $method = $mock->expects($this->once())
            ->method('attach');

        if($property == 'space'){
            $method->with(array_values($event)[0], $listener);
        } else {
            $method->with(array_keys($event)[0], $this->isInstanceOf(\Closure::class));
        }

        $this->setValueProperty($listener, '_eventsManager', $mock);

        $this->setValueProperty($listener, $property, $event);

        $listener->attach();
    }

    /**
     * @dataProvider dataEvents
     */
    public function testDetach($property, $event){

        $listener = new StubGoodListener;

        $mock = $this->createMock(Manager::class);
        $method = $mock->expects($this->once())
            ->method('attach');

        if($property == 'space'){
            $method->with(array_values($event)[0], $listener);
        } else {
            $method->with(array_keys($event)[0], $this->isInstanceOf(\Closure::class));
        }

        $method = $mock->expects($this->once())
            ->method('detach');

        if($property == 'space'){
            $method->with(array_values($event)[0], $listener);
        } else {
            $method->with(array_keys($event)[0], $this->isInstanceOf(\Closure::class));
        }

        $this->setValueProperty($listener, '_eventsManager', $mock);

        $this->setValueProperty($listener, $property, $event);

        $listener->attach();

        $listener->detach();
    }

    public function testApplicationLifeCycle()
    {
        // GIVEN
        $lifeCycle = [
            'onBoot',
            'beforeHandleRequest',
            'beforeDispatchLoop',
            'beforeDispatch',
            'beforeExecuteRoute',
            'afterInitialize',
            'afterExecuteRoute',
            'afterDispatch',
            'afterDispatchLoop',
        ];

        // WHEN
        $this->dispatch('/');

        $viewed = StubListener::$instance->views;

        $i = 0;
        foreach ($viewed as $func => $funcViewed) {
            $this->assertEquals($lifeCycle[$i++], $func);
            $this->assertCount(1, $funcViewed);
        }
    }
}

class StubGoodListener extends Listener
{
    public function test(){}
}

class StubWrongListener extends Listener
{
    protected $listen = ['test'];
}

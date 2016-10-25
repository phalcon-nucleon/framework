<?php
namespace Test;

use Luxury\Events\Listener;
use Test\Stub\StubListener;
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
    public function testAttach()
    {
        /** @var Listener $listener */
        $listener = new class extends Listener
        {
            protected $listen = ['test'];
        };

        $listener->attach();
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

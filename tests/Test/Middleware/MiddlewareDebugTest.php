<?php

namespace Test\Middleware;

use Luxury\Foundation\Middleware\Debug;
use Luxury\Providers\Logger;
use Luxury\Support\Facades\Log;
use Phalcon\Events\Event;
use Test\TestCase\TestCase;

/**
 * Class MiddlewareDebugTest
 *
 * @package Test\Middleware
 */
class MiddlewareDebugTest extends TestCase
{

    public function dataFunctionDebug()
    {
        return [
            'beforeHandleRequest' => ['beforeHandleRequest',
                                      'application:beforeHandleRequest : handler : '],
            'beforeDispatchLoop'  => ['beforeDispatchLoop',
                                      'dispatcher:beforeDispatchLoop : handler : '],
            'beforeDispatch'      => ['beforeDispatch', 'dispatcher:beforeDispatch : handler : '],
            'beforeExecuteRoute'  => ['beforeExecuteRoute',
                                      'dispatcher:beforeExecuteRoute : handler : '],
            'afterInitialize'     => ['afterInitialize', 'dispatcher:afterInitialize : handler : '],
            'afterExecuteRoute'   => ['afterExecuteRoute',
                                      'dispatcher:afterExecuteRoute : handler : '],
            'afterDispatch'       => ['afterDispatch', 'dispatcher:afterDispatch : handler : '],
            'afterDispatchLoop'   => ['afterDispatchLoop',
                                      'dispatcher:afterDispatchLoop : handler : '],
            'afterHandleRequest'  => ['afterHandleRequest',
                                      'application:afterHandleRequest : handler : '],
        ];
    }

    /**
     * @dataProvider dataFunctionDebug
     *
     * @param $function
     * @param $log
     */
    public function testFunction($function, $log)
    {
        $this->app->config->log          = new \stdClass();
        $this->app->config->log->adapter = 'Multiple';
        $this->app->config->log->path  = __DIR__ . '/../../.data/';
        $this->app->config->log->options = new \Phalcon\Config([]);

        $provider = new Logger();

        $provider->registering();
        
        Log::shouldReceive('debug')->once()->with($log . 'stdClass');

        /** @var Debug $debug */
        $debug = new class extends Debug
        {

        };

        $debug->$function(new Event('', new \stdClass), new \stdClass);
    }
}

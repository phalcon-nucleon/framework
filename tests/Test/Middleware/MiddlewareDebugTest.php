<?php

namespace Test\Middleware;

use Luxury\Constants\Services;
use Luxury\Foundation\Middleware\Debug;
use Phalcon\Events\Event;
use Phalcon\Logger\Adapter\File;
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
        $this->mockService(Services::LOGGER, File\Multiple::class, true)
            ->expects($this->once())
            ->method('debug')
            ->with($log . 'stdClass');

        /** @var Debug $debug */
        $debug = new Debug;

        $debug->$function(new Event('', new \stdClass), new \stdClass);
    }
}

<?php

namespace Luxury\Foundation\Middleware;

use Luxury\Constants\Events;
use Luxury\Events\Listener;
use Luxury\Support\Facades\Log;
use Phalcon\Events\Event;

/**
 * Class Debug
 *
 * @package Luxury\Middleware
 */
class Debug extends Listener
{
    protected $space = [
        Events::APPLICATION,
        Events::DISPATCH
    ];

    /**
     * Event : application:beforeHandleRequest
     *
     * @param \Phalcon\Events\Event $event
     * @param mixed                 $handler
     */
    public function beforeHandleRequest(Event $event, $handler)
    {
        Log::debug('application:beforeHandleRequest : handler : ' . get_class($handler));
    }

    /**
     * Event : dispatcher:beforeDispatchLoop
     *
     * @param \Phalcon\Events\Event $event
     * @param mixed                 $handler
     */
    public function beforeDispatchLoop(Event $event, $handler)
    {
        Log::debug('dispatcher:beforeDispatchLoop : handler : ' . get_class($handler));
    }

    /**
     * Event : dispatcher:beforeDispatch
     *
     * @param \Phalcon\Events\Event $event
     * @param mixed                 $handler
     */
    public function beforeDispatch(Event $event, $handler)
    {
        Log::debug('dispatcher:beforeDispatch : handler : ' . get_class($handler));
    }

    /**
     * Event : dispatcher:beforeExecuteRoute
     *
     * @param \Phalcon\Events\Event $event
     * @param mixed                 $handler
     */
    public function beforeExecuteRoute(Event $event, $handler)
    {
        Log::debug('dispatcher:beforeExecuteRoute : handler : ' . get_class($handler));
    }

    /**
     * Event : dispatcher:afterInitialize
     *
     * @param \Phalcon\Events\Event $event
     * @param mixed                 $handler
     */
    public function afterInitialize(Event $event, $handler)
    {
        Log::debug('dispatcher:afterInitialize : handler : ' . get_class($handler));
    }

    /**
     * Event : dispatcher:afterExecuteRoute
     *
     * @param \Phalcon\Events\Event $event
     * @param mixed                 $handler
     */
    public function afterExecuteRoute(Event $event, $handler)
    {
        Log::debug('dispatcher:afterExecuteRoute : handler : ' . get_class($handler));
    }

    /**
     * Event : dispatcher:afterDispatch
     *
     * @param \Phalcon\Events\Event $event
     * @param mixed                 $handler
     */
    public function afterDispatch(Event $event, $handler)
    {
        Log::debug('dispatcher:afterDispatch : handler : ' . get_class($handler));
    }

    /**
     * Event : dispatcher:afterDispatchLoop
     *
     * @param \Phalcon\Events\Event $event
     * @param mixed                 $handler
     */
    public function afterDispatchLoop(Event $event, $handler)
    {
        Log::debug('dispatcher:afterDispatchLoop : handler : ' . get_class($handler));
    }

    /**
     * Event : application:afterHandleRequest
     *
     * @param \Phalcon\Events\Event $event
     * @param mixed                 $handler
     */
    public function afterHandleRequest(Event $event, $handler)
    {
        Log::debug('application:afterHandleRequest : handler : ' . get_class($handler));
    }
}

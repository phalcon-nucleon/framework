<?php

namespace Luxury\Middleware;

use Phalcon\Events\Event;

/**
 * Interface AfterMiddleware
 *
 * @package Luxury\Middleware
 *
 * Middleware after handled
 */
interface AfterMiddleware
{
    /**
     * Called after the execution of handler
     *
     * @param \Phalcon\Events\Event $event
     * @param \Phalcon\Dispatcher|mixed   $source
     * @param mixed|null                  $data
     *
     * @throws \Exception
     * @return bool
     */
    public function after(Event $event, $source, $data = null);
}

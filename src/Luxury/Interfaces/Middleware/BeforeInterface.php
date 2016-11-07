<?php

namespace Luxury\Interfaces\Middleware;

use Phalcon\Events\Event;

/**
 * Interface BeforeMiddleware
 *
 * @package Luxury\Middleware
 *
 * Middleware before handled
 */
interface BeforeInterface
{
    /**
     * Called before the execution of handler
     *
     * @param \Phalcon\Events\Event $event
     * @param \Phalcon\Dispatcher|mixed   $source
     * @param mixed|null                  $data
     *
     * @throws \Exception
     * @return bool
     */
    public function before(Event $event, $source, $data = null);
}

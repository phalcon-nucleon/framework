<?php

namespace Luxury\Middleware;

use Phalcon\Events\Event;

/**
 * Interface FinishMiddleware
 *
 * @package Luxury\Middleware
 *
 * Middleware finish handled
 */
interface FinishMiddleware
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
    public function finish(Event $event, $source, $data = null);
}

<?php

namespace Luxury\Middleware;

/**
 * Interface BeforeMiddleware
 *
 * @package Luxury\Middleware
 *
 * Middleware before handled
 */
interface BeforeMiddleware
{
    /**
     * Called before the execution of handler
     *
     * @param \Phalcon\Events\Event|mixed $event
     * @param \Phalcon\Dispatcher|mixed   $source
     * @param mixed|null                  $data
     *
     * @throws \Exception
     * @return bool
     */
    public function before($event, $source, $data = null);
}

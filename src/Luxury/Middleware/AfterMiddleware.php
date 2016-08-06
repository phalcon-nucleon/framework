<?php

namespace Luxury\Middleware;

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
     * @param \Phalcon\Events\Event|mixed $event
     * @param \Phalcon\Dispatcher|mixed   $source
     * @param mixed|null                  $data
     *
     * @throws \Exception
     * @return bool
     */
    public function after($event, $source, $data = null);
}

<?php

namespace Luxury\Middleware;

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
     * @param \Phalcon\Events\Event|mixed $event
     * @param \Phalcon\Dispatcher|mixed   $source
     * @param mixed|null                  $data
     *
     * @throws \Exception
     * @return bool
     */
    public function finish($event, $source, $data = null);
}

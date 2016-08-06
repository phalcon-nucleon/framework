<?php

namespace Luxury\Middleware;

/**
 * Interface InitMiddleware
 *
 * @package Luxury\Middleware
 *
 * Middleware finish handled
 */
interface InitMiddleware
{
    /**
     * Called on the initialization
     *
     * @param \Phalcon\Events\Event|mixed $event
     * @param \Phalcon\Dispatcher|mixed   $source
     * @param mixed|null                  $data
     *
     * @throws \Exception
     * @return bool
     */
    public function init($event, $source, $data = null);
}

<?php

namespace Luxury\Foundation;

use Luxury\Foundation\Middleware\Controller as ControllerMiddleware;

/**
 * Class Controller
 *
 * @package Luxury\Foundation
 *
 * @property-read \Phalcon\Application|\Luxury\Foundation\Kernelize $app
 */
abstract class Controller extends \Phalcon\Mvc\Controller
{
    /**
     * Event called on controller construction
     *
     * Register middleware here.
     */
    abstract protected function onConstruct();

    /**
     * Attach a ControllerMiddleware
     *
     * @param ControllerMiddleware $middleware
     *
     * @return ControllerMiddleware
     */
    protected function middleware(ControllerMiddleware $middleware)
    {
        $this->app->attachMiddleware($middleware);

        return $middleware;
    }
}

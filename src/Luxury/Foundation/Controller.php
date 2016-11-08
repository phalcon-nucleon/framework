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
     * Attach a ControllerMiddleware.
     *
     * On controllers, only ControllerMiddleware are attachable,
     * because the middleware registration, passed by the controller, will made at the controller instantiation.
     * Because of this, the events
     *  "Application::boot"
     *  "Dispatch::beforeDispatchLoop"
     *  "Dispatch::BeforeDispatch"
     * can not be caught.
     *
     * @param ControllerMiddleware $middleware
     *
     * @return Controller
     */
    protected function middleware(ControllerMiddleware $middleware)
    {
        $this->app->attach($middleware);

        return $this;
    }
}

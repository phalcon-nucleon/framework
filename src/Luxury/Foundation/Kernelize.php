<?php

namespace Luxury\Foundation;

use Luxury\Constants\Services;
use Luxury\Events\Listener;
use Luxury\Middleware\Middleware;
use Luxury\Support\Facades\Facade;
use Phalcon\Di;
use Phalcon\Di\Service;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Loader;

/**
 * Class HttpKernel
 *
 * @package Luxury\Foundation
 */
trait Kernelize
{
    /**
     * This methods registers the services to be used by the application
     */
    public function registerServices()
    {
        foreach ($this->providers as $provider) {
            /* @var \Luxury\Interfaces\Providable $prv */
            $prv = new $provider();

            /** @var \Phalcon\Application $this */
            $prv->register($this->getDI());
        }
    }

    /**
     * This methods registers the middlewares to be used by the application
     */
    public function registerMiddlewares()
    {
        foreach ($this->middlewares as $middleware) {
            $this->attachMiddleware(new $middleware);
        }
    }

    /**
     * Attach an Event Listener
     *
     * @param Listener $listener
     *
     * @throws \Exception
     */
    public function attach(Listener $listener)
    {
        /** @var \Phalcon\Application $this */
        $listener->setDI($this->getDI());

        $listener->setEventsManager($this->getEventsManager());

        $listener->attach();
    }

    /**
     * Attach a Middleware
     *
     * @param Middleware $middleware
     *
     * @throws \Exception
     */
    public function attachMiddleware(Middleware $middleware)
    {
        $this->attach($middleware);
    }

    public function bootstrap()
    {
        $diClass = $this->dependencyInjection;

        /** @var \Phalcon\Application $this */
        $em = new EventsManager;

        $this->setEventsManager($em);

        Di::reset();

        /** @var Di $di */
        $di = new $diClass;

        $di->setShared('app', $this);

        $di->setInternalEventsManager($em);

        $di->setShared(Services::EVENTS_MANAGER, $em);

        // Register Global Di
        Di::setDefault($di);

        // Register Di on Facade
        Facade::setDependencyInjection($di);

        // Register Di on Application
        $this->setDI($di);
    }
}

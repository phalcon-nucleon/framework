<?php

namespace Luxury\Foundation;

use Luxury\Constants\Services;
use Luxury\Events\Listener;
use Luxury\Middleware\Middleware;
use Luxury\Support\Facades\Facade;
use Phalcon\Config;
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
            /* @var \Luxury\Providers\Provider $prv */
            $prv = new $provider();

            $prv->registering();
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
     * This methods registers the middlewares to be used by the application
     */
    public function registerListeners()
    {
        foreach ($this->listeners as $listener) {
            $this->attach(new $listener);
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

    /**
     * Application starter
     *
     * @param \Phalcon\Config $config
     *
     * @return void
     */
    public function bootstrap(Config $config)
    {
        $diClass = $this->dependencyInjection;

        /** @var \Phalcon\Application $this */
        $em = new EventsManager;

        $this->setEventsManager($em);

        Di::reset();

        /** @var Di $di */
        $di = new $diClass;

        $di->setShared(Services::APP, $this);
        $di->setShared(Services::CONFIG, $config);

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

<?php

namespace Neutrino\Foundation;

use Neutrino\Constants\Services;
use Neutrino\Error\Handler;
use Neutrino\Events\Listener;
use Neutrino\Support\Facades\Facade;
use Phalcon\Config;
use Phalcon\Di;

/**
 * Class HttpKernel
 *
 * @package Neutrino\Foundation
 */
trait Kernelize
{
    /**
     * This methods registers the services to be used by the application
     */
    public function registerServices()
    {
        /** @var Di $di */
        $di = $this->getDI();

        foreach ($this->providers as $name => $provider) {
            if (is_string($name)) {
                $service = new Di\Service($name, $provider, true);

                $di->setRaw($name, $service);
                $di->setRaw($provider, $service);

                continue;
            }

            /* @var \Neutrino\Interfaces\Providable $prv */
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
            $this->attach(new $middleware);
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
     * This methods registers the middlewares to be used by the application
     *
     * @param array $modules
     * @param bool  $merge
     */
    public function registerModules(array $modules, $merge = false)
    {
        if (!empty($this->modules) || !empty($modules)) {
            parent::registerModules(array_merge($this->modules, $modules), $merge);
        }
    }

    /**
     * Attach an Listener
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
     * Application starter
     *
     * @param \Phalcon\Config $config
     *
     * @return void
     */
    public final function bootstrap(Config $config)
    {
        /** @var \Phalcon\Application $this */
        Handler::setWriter(...$this->errorHandlerLvl);

        $diClass = $this->dependencyInjection;

        if (empty($diClass)) {
            $di = Di::getDefault();
        } else {
            Di::reset();

            /** @var Di $di */
            $di = new $diClass;

            // Global Register Di
            Di::setDefault($di);
        }

        // Register Di on Application
        $this->setDI($di);

        // Register Default Shared instance
        $di->setShared(Services::APP, $this);
        $di->setShared(Services::CONFIG, $config);

        $emClass = $this->eventsManagerClass;

        if (!empty($emClass)) {
            $em = new $emClass;

            $this->setEventsManager($em);

            $di->setInternalEventsManager($em);

            $di->setShared(Services::EVENTS_MANAGER, $em);
        }

        // Register Di on Facade
        Facade::setDependencyInjection($di);
    }

    /**
     * @return void
     */
    public function boot()
    {
        if (!is_null($em = $this->getEventsManager())) {
            $em->fire(\Neutrino\Constants\Events\Kernel::BOOT, $this);
        }
    }

    /**
     * @return void
     */
    public function terminate()
    {
        if (!is_null($em = $this->getEventsManager())) {
            $em->fire(\Neutrino\Constants\Events\Kernel::TERMINATE, $this);
        }
    }
}

<?php

namespace Neutrino\Foundation\Http;

use Neutrino\Error;
use Neutrino\Foundation\Kernelize;
use Neutrino\Interfaces\Kernelable;
use Phalcon\Di\FactoryDefault as Di;
use Phalcon\Events\Manager as EventManager;
use Phalcon\Mvc\Application;

/**
 * Class Http
 *
 * @package Neutrino\Foundation\Kernel
 */
abstract class Kernel extends Application implements Kernelable
{
    use Kernelize {
        boot as _boot;
    }

    /**
     * Return the Provider List to load.
     *
     * @var string[]
     */
    protected $providers = [];

    /**
     * Return the Middlewares to attach onto the application.
     *
     * @var string[]
     */
    protected $middlewares = [];

    /**
     * Return the Events Listeners to attach onto the application.
     *
     * @var string[]
     */
    protected $listeners = [];

    /**
     * Return the modules to attach onto the application.
     *
     * @var string[]
     */
    protected $modules = [];

    /**
     * The DependencyInjection class to use.
     *
     * @var string
     */
    protected $dependencyInjection = Di::class;

    /**
     * The EventManager class to use.
     *
     * @var string
     */
    protected $eventsManagerClass = EventManager::class;

    /**
     * Register the routes of the application.
     */
    public function registerRoutes()
    {
        if (file_exists(BASE_PATH . '/bootstrap/compile/http-routes.php')) {
            require BASE_PATH . '/bootstrap/compile/http-routes.php';
        } else {
            require BASE_PATH . '/routes/http.php';
        }
    }

    public function boot()
    {
        $this->_boot();

        $this->useImplicitView(isset($this->config->view->implicit) ? $this->config->view->implicit : false);
    }
}

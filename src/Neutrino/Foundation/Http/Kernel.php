<?php

namespace Neutrino\Foundation\Http;

use Neutrino\Foundation\Kernelize;
use Neutrino\Interfaces\Kernelable;
use Phalcon\Config;
use Phalcon\Di\FactoryDefault as Di;
use Phalcon\Mvc\Application;

/**
 * Class Http
 *
 * @package Neutrino\Foundation\Kernel
 */
abstract class Kernel extends Application implements Kernelable
{
    use Kernelize;

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
     * The DependencyInjection class to use.
     *
     * @var string
     */
    protected $dependencyInjection = Di::class;

    /**
     * Register the routes of the application.
     */
    public function registerRoutes()
    {
        require $this->config->paths->routes . 'http.php';
    }

    public function boot()
    {
        $this->useImplicitView(isset($this->config->view->implicit) ? $this->config->view->implicit : false);
    }
}

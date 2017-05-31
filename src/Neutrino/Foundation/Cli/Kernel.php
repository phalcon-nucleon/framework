<?php

namespace Neutrino\Foundation\Cli;

use Neutrino\Foundation\Kernelize;
use Neutrino\Interfaces\Kernelable;
use Phalcon\Cli\Console;
use Phalcon\Di\FactoryDefault\Cli as Di;
use Phalcon\Events\Manager as EventManager;

/**
 * Class Cli
 *
 *  @package Neutrino\Foundation\Kernel
 *
 * @property-read \Neutrino\Cli\Router    $router
 * @property-read \Phalcon\Cli\Dispatcher $dispatcher
 */
abstract class Kernel extends Console implements Kernelable
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
     * Application constructor.
     */
    public function __construct()
    {
        parent::__construct(null);
    }

    /**
     * Register the routes of the application.
     */
    public function registerRoutes()
    {
        require BASE_PATH .'/routes/cli.php';
    }
}

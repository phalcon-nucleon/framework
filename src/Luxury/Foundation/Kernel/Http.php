<?php

namespace Luxury\Foundation\Kernel;

use Luxury\Foundation\Kernelize;
use Luxury\Interfaces\Kernelable;
use Phalcon\Config;
use Phalcon\Di\FactoryDefault as Di;
use Phalcon\Mvc\Application as PhApplication;

/**
 * Class Http
 *
 * @package Luxury\Foundation\Kernel
 */
abstract class Http extends PhApplication implements Kernelable
{
    use Kernelize {
        bootstrap as kernelizeBootstrap;
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
     * The DependencyInjection class to use.
     *
     * @var string
     */
    protected $dependencyInjection = Di::class;

    /**
     * Application constructor.
     */
    public function __construct()
    {
        parent::__construct(null);
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
        $this->kernelizeBootstrap($config);

        $this->useImplicitView($config->app->useImplicitView ?? false);
    }
}

<?php

namespace Luxury\Foundation\Application;

use Luxury\Foundation\Kernelize;
use Luxury\Interfaces\Kernelable;
use Phalcon\Config;
use Phalcon\Di\FactoryDefault as Di;
use Phalcon\Mvc\Application as PhApplication;

/**
 * Class Http
 *
 * @package Luxury\Foundation\Application
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
     * Return the Middleware List to load.
     *
     * @var string[]
     */
    protected $middlewares = [];

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

    public function bootstrap(Config $config)
    {
        $this->kernelizeBootstrap($config);

        $this->useImplicitView($config->application->useImplicitView ?? false);
    }
}

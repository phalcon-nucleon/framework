<?php

namespace Luxury\Foundation;

use Phalcon\Config;

/**
 * Class Application
 *
 * Phalcon Application Boostraper
 *
 * @package Luxury\Foundation
 */
class Bootstrap
{
    /**
     * @var \Phalcon\Config
     */
    private $config;

    /**
     * Application constructor.
     *
     * @param \Phalcon\Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param $kernelClass
     *
     * @return \Phalcon\Application
     */
    public function make($kernelClass)
    {
        /** @var \Phalcon\Application|\Luxury\Interfaces\Kernelable $kernel */
        $kernel = new $kernelClass;

        $kernel->bootstrap($this->config);

        $kernel->registerServices();
        $kernel->registerMiddlewares();
        $kernel->registerListeners();
        $kernel->registerRoutes();

        return $kernel;
    }
}

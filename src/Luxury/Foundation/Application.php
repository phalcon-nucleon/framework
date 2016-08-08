<?php

namespace Luxury\Foundation;

use Luxury\Constants\Services;
use Phalcon\Config;

/**
 * Class Application
 *
 * @package Luxury\Foundation
 */
class Application
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
        /** @var \Phalcon\Application|\Luxury\Interfaces\Kernel $kernel */
        $kernel = new $kernelClass;

        $kernel->bootstrap();

        $kernel->getDI()->setShared(Services::CONFIG, $this->config);

        $kernel->registerServices();
        $kernel->registerMiddlewares();
        $kernel->registerRoutes();

        return $kernel;
    }
}

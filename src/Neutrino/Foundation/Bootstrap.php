<?php

namespace Neutrino\Foundation;

use Neutrino\Interfaces\Kernelable;
use Phalcon\Config;
use Phalcon\Http\Response;

/**
 * Class Application
 *
 * Phalcon Application Bootstrap
 *
 * @package Neutrino\Foundation
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
        /** @var \Phalcon\Application|\Neutrino\Interfaces\Kernelable $kernel */
        $kernel = new $kernelClass;

        $kernel->bootstrap($this->config);

        $kernel->registerServices();
        $kernel->registerMiddlewares();
        $kernel->registerListeners();
        $kernel->registerRoutes();
        $kernel->registerModules([]);

        return $kernel;
    }

    /**
     * @param \Neutrino\Interfaces\Kernelable|\Phalcon\Application $kernel
     */
    public function run(Kernelable $kernel)
    {
        $kernel->boot();

        if (($response = $kernel->handle()) instanceof Response) {
            if (!$response->isSent()) {
                $response->send();
            }
        };

        $kernel->terminate();
    }
}

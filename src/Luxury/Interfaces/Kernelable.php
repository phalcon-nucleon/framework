<?php

namespace Luxury\Interfaces;

use Phalcon\Application;
use Phalcon\Config;

/**
 * Interface KernelInterface
 *
 * @package Luxury\Interfaces
 *
 * @property-read Config|\stdClass|array config
 */
interface Kernelable
{
    /**
     * Application starter
     *
     * @param Config $config
     *
     * @return void
     */
    public function bootstrap(Config $config);

    /**
     * Register the services.
     *
     * @return void
     */
    public function registerServices();

    /**
     * Register the routes.
     *
     * @return void
     */
    public function registerRoutes();

    /**
     * Register the middlewares.
     *
     * @return void
     */
    public function registerMiddlewares();

    /**
     * Register the events listeners.
     *
     * @return void
     */
    public function registerListeners();
}

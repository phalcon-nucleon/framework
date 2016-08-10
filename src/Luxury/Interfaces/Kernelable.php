<?php

namespace Luxury\Interfaces;

use Phalcon\Application;

/**
 * Interface KernelInterface
 *
 * @package Luxury\Interfaces
 *
 * @property-read \Phalcon\Config|\stdClass|array config
 */
interface Kernelable
{
    /**
     * Application starter
     *
     * @return void
     */
    public function bootstrap();

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
}

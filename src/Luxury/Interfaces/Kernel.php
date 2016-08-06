<?php

namespace Luxury\Interfaces;

use Phalcon\Application;

/**
 * Interface KernelInterface
 *
 * @package Luxury\Interfaces
 */
interface Kernel
{
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

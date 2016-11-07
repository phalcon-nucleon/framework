<?php

namespace Luxury\Interfaces;

/**
 * Class Providable
 *
 * @package Luxury\Providers
 */
interface Providable
{
    /**
     * Called upon bootstrap the application.
     * Adds to container services desired services.
     *
     * @return void
     */
    public function registering();
}

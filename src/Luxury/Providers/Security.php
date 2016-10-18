<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Security\SecurityPlugin;


/**
 * Class Security
 *
 * @package Luxury\Bootstrap\Services
 */
class Security extends Provider
{
    protected $name = Services::SECURITY;

    protected $shared = true;

    /**
     * @return \Luxury\Security\SecurityPlugin
     */
    protected function register()
    {
        return new SecurityPlugin;
    }
}

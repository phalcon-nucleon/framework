<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;

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
     * @return \Phalcon\Security
     */
    protected function register()
    {
        return new \Phalcon\Security;
    }
}

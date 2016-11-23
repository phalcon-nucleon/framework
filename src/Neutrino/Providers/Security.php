<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;

/**
 * Class Security
 *
 *  @package Neutrino\Providers
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

<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;

/**
 * Class Crypt
 *
 *  @package Neutrino\Providers
 */
class Crypt extends Provider
{
    protected $name = Services::CRYPT;

    protected $shared = true;

    /**
     * @return \Phalcon\Crypt
     */
    protected function register()
    {
        return new \Phalcon\Crypt;
    }
}

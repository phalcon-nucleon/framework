<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;

use Phalcon\Flash\Session;

/**
 * Class FlashSession
 *
 *  @package Neutrino\Providers
 */
class FlashSession extends Provider
{
    protected $name = Services::FLASH_SESSION;

    protected $shared = true;

    protected $aliases = [Session::class];

    /**
     * @return \Phalcon\Flash\Session
     */
    protected function register()
    {
        return new Session;
    }
}

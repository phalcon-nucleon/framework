<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;

use Phalcon\Flash\Session;

/**
 * Class FlashSession
 *
 * @package Luxury\Providers
 */
class FlashSession extends Provider
{
    protected $name = Services::FLASH_SESSION;

    protected $shared = true;

    /**
     * @return \Phalcon\Flash\Session
     */
    protected function register()
    {
        return new Session;
    }
}

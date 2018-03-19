<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;

use Neutrino\Support\Provider;
use Phalcon\Flash\Session as PhalconFlashSession;

/**
 * Class FlashSession
 *
 *  @package Neutrino\Providers
 */
class FlashSession extends Provider
{
    protected $name = Services::FLASH_SESSION;

    protected $shared = true;

    protected $aliases = [PhalconFlashSession::class];

    /**
     * @return \Phalcon\Flash\Session
     */
    protected function register()
    {
        $flash = new PhalconFlashSession();

        return $flash;
    }
}

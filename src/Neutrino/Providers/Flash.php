<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;

use Neutrino\Support\Provider;
use Phalcon\Flash\Direct as PhalconFlashDirect;

/**
 * Class Flash
 *
 * @package Neutrino\Foundation\Bootstrap
 */
class Flash extends Provider
{
    protected $name = Services::FLASH;

    protected $shared = false;

    protected $aliases = [PhalconFlashDirect::class];

    /**
     * @return \Phalcon\Flash\Direct
     */
    protected function register()
    {
        $flash = new PhalconFlashDirect();

        $flash->setImplicitFlush(false);

        return $flash;
    }
}

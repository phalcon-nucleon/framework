<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;

use Phalcon\Flash\Direct as FlashDirect;

/**
 * Class Flash
 *
 * @package Luxury\Foundation\Bootstrap
 */
class Flash extends Provider
{
    protected $name = Services::FLASH;

    protected $shared = false;

    /**
     * @return \Phalcon\Flash\Direct
     */
    protected function register()
    {
        $flash = new FlashDirect([
            'error'   => 'alert alert-danger',
            'success' => 'alert alert-success',
            'notice'  => 'alert alert-info',
            'warning' => 'alert alert-warning'
        ]);

        $flash->setImplicitFlush(false);

        return $flash;
    }
}

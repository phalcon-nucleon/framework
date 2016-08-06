<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Flash
 *
 * @package Luxury\Foundation\Bootstrap
 */
class Flash implements Providable
{
    /**
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->set(Services::FLASH, function () {
            return new \Phalcon\Flash\Direct([
                'error'   => 'alert alert-danger',
                'success' => 'alert alert-success',
                'notice'  => 'alert alert-info',
                'warning' => 'alert alert-warning'
            ]);
        });

        $di->setShared(Services::FLASH_SESSION, \Phalcon\Flash\Session::class);
    }
}

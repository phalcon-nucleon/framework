<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Phalcon\DiInterface;

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
     * @param \Phalcon\DiInterface $di
     *
     * @return \Phalcon\Flash\Direct
     */
    protected function register(DiInterface $di)
    {
        return new \Phalcon\Flash\Direct([
            'error'   => 'alert alert-danger',
            'success' => 'alert alert-success',
            'notice'  => 'alert alert-info',
            'warning' => 'alert alert-warning'
        ]);
    }
}

<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;


/**
 * Class Escaper
 *
 * @package Luxury\Providers
 */
class Escaper extends Provider
{
    protected $name = Services::ESCAPER;

    protected $shared = true;

    /**
     * @return \Phalcon\Escaper
     */
    protected function register()
    {
        return new \Phalcon\Escaper;
    }
}

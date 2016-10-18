<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;


/**
 * Class Cookies
 *
 * @package Luxury\Providers
 */
class Cookies extends Provider
{

    protected $name = Services::COOKIES;

    protected $shared = true;

    /**
     * @return \Phalcon\Http\Response\Cookies
     */
    protected function register()
    {
        return new \Phalcon\Http\Response\Cookies;
    }
}

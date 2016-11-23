<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;


/**
 * Class Cookies
 *
 *  @package Neutrino\Providers
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

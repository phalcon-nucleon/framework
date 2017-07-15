<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\SimpleProvider;


/**
 * Class Cookies
 *
 *  @package Neutrino\Providers
 */
class Cookies extends SimpleProvider
{
    protected $class = \Phalcon\Http\Response\Cookies::class;

    protected $name = Services::COOKIES;

    protected $shared = true;

    protected $aliases = [\Phalcon\Http\Response\Cookies::class];
}

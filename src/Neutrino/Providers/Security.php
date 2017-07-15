<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\SimpleProvider;

/**
 * Class Security
 *
 *  @package Neutrino\Providers
 */
class Security extends SimpleProvider
{
    protected $class = \Phalcon\Security::class;

    protected $name = Services::SECURITY;

    protected $shared = true;

    protected $aliases = [\Phalcon\Security::class];
}

<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\SimpleProvider;

/**
 * Class Crypt
 *
 *  @package Neutrino\Providers
 */
class Crypt extends SimpleProvider
{
    protected $class = \Phalcon\Crypt::class;

    protected $name = Services::CRYPT;

    protected $shared = true;

    protected $aliases = [\Phalcon\Crypt::class];
}

<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\SimpleProvider;


/**
 * Class Escaper
 *
 *  @package Neutrino\Providers
 */
class Escaper extends SimpleProvider
{
    protected $class = \Phalcon\Escaper::class;

    protected $name = Services::ESCAPER;

    protected $shared = true;

    protected $aliases = [\Phalcon\Escaper::class];
}

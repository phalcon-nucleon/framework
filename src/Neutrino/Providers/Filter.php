<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\SimpleProvider;


/**
 * Class Filter
 *
 *  @package Neutrino\Providers
 */
class Filter extends SimpleProvider
{
    protected $class = \Phalcon\Filter::class;

    protected $name = Services::FILTER;

    protected $shared = true;

    protected $aliases = [\Phalcon\Filter::class];
}

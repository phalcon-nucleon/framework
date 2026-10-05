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
    protected string $class = \Phalcon\Filter::class;

    protected string $name = Services::FILTER;

    protected bool $shared = true;

    protected array $aliases = [\Phalcon\Filter::class];
}

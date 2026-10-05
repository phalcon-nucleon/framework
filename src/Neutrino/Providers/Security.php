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
    protected string $class = \Phalcon\Security::class;

    protected string $name = Services::SECURITY;

    protected bool $shared = true;

    protected array $aliases = [\Phalcon\Security::class];
}

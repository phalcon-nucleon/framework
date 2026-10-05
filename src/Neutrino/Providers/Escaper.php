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
    protected string $class = \Phalcon\Escaper::class;

    protected string $name = Services::ESCAPER;

    protected bool $shared = true;

    protected array $aliases = [\Phalcon\Escaper::class];
}

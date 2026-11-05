<?php

declare(strict_types=1);

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
    protected string $class = \Phalcon\Http\Response\Cookies::class;

    protected string $name = Services::COOKIES;

    protected bool $shared = true;

    protected array $aliases = [\Phalcon\Http\Response\Cookies::class];
}

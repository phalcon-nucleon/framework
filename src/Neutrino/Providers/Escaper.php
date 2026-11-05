<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\SimpleProvider;
use Phalcon\Html\Escaper as PhalconEscaper;

/**
 * The `escaper` service.
 */
class Escaper extends SimpleProvider
{
    protected string $class = PhalconEscaper::class;

    protected string $name = Services::ESCAPER;

    protected bool $shared = true;

    protected array $aliases = [PhalconEscaper::class];
}

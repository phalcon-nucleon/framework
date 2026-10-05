<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\SimpleProvider;
use Phalcon\Annotations\Adapter\Memory as AnnotationsMemory;


/**
 * Class Annotations
 *
 *  @package Neutrino\Providers
 */
class Annotations extends SimpleProvider
{
    protected string $class = AnnotationsMemory::class;

    protected string $name = Services::ANNOTATIONS;

    protected bool $shared = true;

    protected array $aliases = [AnnotationsMemory::class];
}

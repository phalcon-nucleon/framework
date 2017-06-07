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
    protected $class = AnnotationsMemory::class;

    protected $name = Services::ANNOTATIONS;

    protected $shared = true;

    protected $aliases = [AnnotationsMemory::class];
}

<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Phalcon\Annotations\Adapter\Memory as AnnotationsMemory;


/**
 * Class Annotations
 *
 *  @package Neutrino\Providers
 */
class Annotations extends Provider
{
    protected $name = Services::ANNOTATIONS;

    protected $shared = true;

    protected $aliases = [AnnotationsMemory::class];

    /**
     * @return \Phalcon\Annotations\Adapter\Memory
     */
    protected function register()
    {
        return new AnnotationsMemory;
    }
}

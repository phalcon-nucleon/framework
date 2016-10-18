<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Phalcon\Annotations\Adapter\Memory as AnnotationsMemory;


/**
 * Class Annotations
 *
 * @package Luxury\Providers
 */
class Annotations extends Provider
{
    protected $name = Services::ANNOTATIONS;

    protected $shared = true;

    /**
     * @return \Phalcon\Annotations\Adapter\Memory
     */
    protected function register()
    {
        return new AnnotationsMemory;
    }
}

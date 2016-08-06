<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Annotations
 *
 * @package Luxury\Bootstrap\Services
 */
class Annotations implements Providable
{
    /**
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::ANNOTATIONS, \Phalcon\Annotations\Adapter\Memory::class);
    }
}

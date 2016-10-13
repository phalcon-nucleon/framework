<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Phalcon\DiInterface;

/**
 * Class Escaper
 *
 * @package Luxury\Bootstrap\Services
 */
class Escaper extends Provider
{
    protected $name = Services::ESCAPER;

    protected $shared = true;

    /**
     * @param \Phalcon\DiInterface $di
     *
     * @return \Phalcon\Escaper
     */
    protected function register(DiInterface $di)
    {
        return new \Phalcon\Escaper;
    }
}

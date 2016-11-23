<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;


/**
 * Class Escaper
 *
 *  @package Neutrino\Providers
 */
class Escaper extends Provider
{
    protected $name = Services::ESCAPER;

    protected $shared = true;

    /**
     * @return \Phalcon\Escaper
     */
    protected function register()
    {
        return new \Phalcon\Escaper;
    }
}

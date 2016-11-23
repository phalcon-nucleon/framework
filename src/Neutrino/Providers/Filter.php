<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;


/**
 * Class Filter
 *
 *  @package Neutrino\Providers
 */
class Filter extends Provider
{
    protected $name = Services::FILTER;

    protected $shared = true;

    /**
     * @return mixed|\Phalcon\Filter
     */
    protected function register()
    {
        return new \Phalcon\Filter;
    }
}

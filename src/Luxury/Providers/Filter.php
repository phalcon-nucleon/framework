<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;


/**
 * Class Filter
 *
 * @package Luxury\Bootstrap\Services
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

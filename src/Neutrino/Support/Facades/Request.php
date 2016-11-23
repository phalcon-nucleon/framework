<?php

namespace Neutrino\Support\Facades;

use Neutrino\Constants\Services;

/**
 * Class Request
 *
 *  @package Neutrino\Support\Facades
 */
class Request extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Services::REQUEST;
    }
}

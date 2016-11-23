<?php

namespace Neutrino\Support\Facades;

use Neutrino\Constants\Services;

/**
 * Class Response
 *
 *  @package Neutrino\Support\Facades
 */
class Response extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Services::RESPONSE;
    }
}

<?php

namespace Luxury\Support\Facades;

use Luxury\Constants\Services;

/**
 * Class Response
 *
 * @package Luxury\Support\Facades
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

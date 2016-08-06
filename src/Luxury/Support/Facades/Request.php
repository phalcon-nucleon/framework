<?php

namespace Luxury\Support\Facades;

use Luxury\Constants\Services;

/**
 * Class Request
 *
 * @package Luxury\Support\Facades
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

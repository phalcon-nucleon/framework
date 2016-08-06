<?php

namespace Luxury\Support\Facades;

use Luxury\Constants\Services;

/**
 * Class View
 *
 * @package Luxury\Support\Facades
 */
class View extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Services::VIEW;
    }
}

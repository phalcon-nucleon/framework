<?php

namespace Neutrino\Support\Facades;

use Neutrino\Constants\Services;

/**
 * Class View
 *
 *  @package Neutrino\Support\Facades
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

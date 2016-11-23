<?php
namespace Neutrino\Support\Facades;

use Neutrino\Constants\Services;

/**
 * Class Flash
 *
 *  @package Neutrino\Support\Facades
 */
class Flash extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Services::FLASH;
    }
}

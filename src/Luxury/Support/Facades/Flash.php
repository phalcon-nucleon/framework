<?php
namespace Luxury\Support\Facades;

use Luxury\Constants\Services;

/**
 * Class Flash
 *
 * @package Luxury\Support\Facades
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

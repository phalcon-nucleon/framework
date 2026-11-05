<?php

declare(strict_types=1);

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
    protected static function getFacadeAccessor(): string
    {
        return Services::REQUEST;
    }
}

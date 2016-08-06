<?php

namespace Luxury\Support\Facades;

use Luxury\Constants\Services;

/**
 * Class Auth
 *
 * @package Luxury\Support\Facades
 * @method static mixed user()
 * @method static mixed guest()
 * @method static mixed attempt(array $credentials)
 * @method static mixed check()
 * @method static mixed logout()
 * @method static mixed id()
 * @method static mixed login()
 * @method static mixed loginUsingId()
 */
class Auth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Services::AUTH;
    }
}

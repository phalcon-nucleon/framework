<?php

namespace Luxury\Support\Facades;

use Luxury\Constants\Services;

/**
 * Class Auth
 *
 * @package Luxury\Support\Facades
 * @method static \Luxury\Foundation\Auth\User|null user()
 * @method static bool guest()
 * @method static \Luxury\Foundation\Auth\User|null attempt(array $credentials, bool $remember = false)
 * @method static bool check()
 * @method static void logout()
 * @method static mixed id()
 * @method static bool login()
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

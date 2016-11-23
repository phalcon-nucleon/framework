<?php

namespace Neutrino\Support\Facades;

use Neutrino\Constants\Services;

/**
 * Class Auth
 *
 *  @package Neutrino\Support\Facades
 * @method static \Neutrino\Foundation\Auth\User|null user()
 * @method static bool guest()
 * @method static \Neutrino\Foundation\Auth\User|null attempt(array $credentials, bool $remember = false)
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

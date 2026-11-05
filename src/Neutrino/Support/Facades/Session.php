<?php

declare(strict_types=1);

namespace Neutrino\Support\Facades;

use Neutrino\Constants\Services;

/**
 * The `session` service ({@see \Phalcon\Session\Manager}).
 *
 * @method static mixed get(string $key, mixed $defaultValue = null, bool $remove = false)
 * @method static void set(string $key, mixed $value)
 * @method static bool has(string $key)
 * @method static void remove(string $key)
 * @method static string getId()
 * @method static string getName()
 * @method static bool exists() Whether the session is started
 * @method static void destroy()
 * @method static \Phalcon\Session\ManagerInterface regenerateId(bool $deleteOldSession = true)
 */
class Session extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Services::SESSION;
    }
}

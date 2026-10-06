<?php

declare(strict_types=1);

namespace Neutrino\Support\Facades;

use Neutrino\Constants\Services;
use Phalcon\Auth\Adapter\AbstractAdapter;
use Phalcon\Auth\Guard\AbstractGuard;
use Phalcon\Auth\Manager;
use Phalcon\Contracts\Auth\AuthUser;
use Phalcon\Contracts\Auth\Guard\GuardStateful;
use Phalcon\Mvc\ModelInterface;
use RuntimeException;

/**
 * The `auth` service ({@see Manager}), plus `guest()`, `login()` and `loginUsingId()` of Nucleon 1.3.
 *
 * @method static AuthUser|null user()
 * @method static bool check()
 * @method static int|string|null id()
 * @method static bool attempt(array<string, mixed> $credentials = [], bool $remember = false)
 * @method static bool validate(array<string, mixed> $credentials = [])
 * @method static void logout()
 * @method static \Phalcon\Contracts\Auth\Guard\Guard guard(?string $name = null)
 * @method static Manager access(string $accessName)
 */
class Auth extends Facade
{
    public static function guest(): bool
    {
        return !self::manager()->check();
    }

    /**
     * Logs the user in with the default guard.
     */
    public static function login(AuthUser $user, bool $remember = false): void
    {
        self::statefulGuard()->login($user, $remember);
    }

    /**
     * Logs in the user of this primary key (`findFirst($id)` on the model of the default guard).
     */
    public static function loginUsingId(int|string $id, bool $remember = false): ?AuthUser
    {
        $guard = self::statefulGuard();
        $adapter = $guard instanceof AbstractGuard ? $guard->getAdapter() : null;
        $model = $adapter instanceof AbstractAdapter ? $adapter->getModel() : null;

        if ($model === null || !is_subclass_of($model, ModelInterface::class)) {
            throw new RuntimeException('Auth::loginUsingId() needs a guard on the model adapter.');
        }

        $user = $model::findFirst(['conditions' => '[' . self::primaryKey($model) . '] = :id:', 'bind' => ['id' => $id]]);

        if (!$user instanceof AuthUser) {
            return null;
        }

        $guard->login($user, $remember);

        return $user;
    }

    /**
     * The primary key attribute of the model: `findFirst($id)` would read a string as PHQL conditions.
     *
     * @param class-string<ModelInterface<mixed>> $model
     */
    private static function primaryKey(string $model): string
    {
        $instance = new $model();
        $metaData = $instance->getModelsMetaData();
        $keys = $metaData->getPrimaryKeyAttributes($instance);

        if (count($keys) !== 1) {
            throw new RuntimeException('Auth::loginUsingId() needs a model with a single primary key column.');
        }

        $column = $keys[0];
        $map = $metaData->getColumnMap($instance);

        return is_array($map) && is_string($map[$column] ?? null) ? $map[$column] : $column;
    }

    protected static function getFacadeAccessor(): string
    {
        return Services::AUTH;
    }

    private static function manager(): Manager
    {
        /** @var Manager */
        return static::getFacadeRoot();
    }

    private static function statefulGuard(): GuardStateful
    {
        $guard = self::manager()->guard();

        if (!$guard instanceof GuardStateful) {
            throw new RuntimeException('The default auth guard cannot log a user in (' . $guard::class . ').');
        }

        return $guard;
    }
}

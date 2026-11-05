<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Interfaces\Auth\Authenticable;
use Neutrino\Support\Provider;
use Phalcon\Auth\Manager;
use Phalcon\Auth\ManagerFactory;
use Phalcon\Config\Config;
use Phalcon\Encryption\Security;
use RuntimeException;

/**
 * The `auth` service: a `Phalcon\Auth\Manager` built from `config/auth.php` (`guards` and `access`, see
 * `Phalcon\Auth\ManagerFactory`).
 *
 * ```php
 * 'auth' => [
 *     'guards' => [
 *         'web' => [
 *             'type'    => 'session',
 *             'default' => true,
 *             'adapter' => ['name' => 'model', 'options' => ['model' => User::class]],
 *             'options' => ['name' => 'auth', 'rememberTtl' => 1209600],
 *         ],
 *     ],
 * ],
 * ```
 *
 * The `model` adapter reads the user by the column `getAuthIdentifierName()` of an {@see Authenticable} model
 * when no `idColumn` is given. The Nucleon 1.3 form (`auth.model`, and `session.id` as the session key) is
 * converted to this `web` guard, with the 1.3 cookie name `remember_me`.
 *
 * Building the manager starts the session: the session guard takes it from the container.
 */
class Auth extends Provider
{
    protected string $name = Services::AUTH;

    protected bool $shared = true;

    protected array $aliases = [Manager::class];

    protected function register(): Manager
    {
        $di = $this->getDI();

        /** @var Config $config */
        $config = $di->getShared(Services::CONFIG);
        $auth = $config->path('auth');
        $auth = $auth instanceof Config ? $auth->toArray() : [];

        if (!isset($auth['guards'])) {
            $auth = self::legacyConfig($auth, $config->path('session.id'));
        }

        $auth['guards'] = array_map(self::identifierColumn(...), is_array($auth['guards']) ? $auth['guards'] : []);

        /** @var Security $security */
        $security = $di->getShared(Services::SECURITY);

        return (new ManagerFactory($security, $di))->load($auth); // @phpstan-ignore argument.type (checked by the factory)
    }

    /**
     * Nucleon 1.3: `auth.model`, and `session.id` as the session key.
     *
     * @param array<mixed> $auth
     *
     * @return array<mixed>
     */
    private static function legacyConfig(array $auth, mixed $sessionKey): array
    {
        if (!is_string($auth['model'] ?? null)) {
            throw new RuntimeException('Auth: no guard, set "auth.guards".');
        }

        return ['guards' => ['web' => [
            'type'    => 'session',
            'default' => true,
            'adapter' => ['name' => 'model', 'options' => ['model' => $auth['model']]],
            'options' => ['rememberName' => 'remember_me'] + (is_string($sessionKey) && $sessionKey !== '' ? ['name' => $sessionKey] : []),
        ]]];
    }

    private static function identifierColumn(mixed $guard): mixed
    {
        if (!is_array($guard) || !is_array($guard['adapter'] ?? null) || ($guard['adapter']['name'] ?? null) !== 'model') {
            return $guard;
        }

        $options = is_array($guard['adapter']['options'] ?? null) ? $guard['adapter']['options'] : [];
        $model = $options['model'] ?? null;

        if (!isset($options['idColumn']) && is_string($model) && is_subclass_of($model, Authenticable::class)) {
            $options['idColumn'] = $model::getAuthIdentifierName();
            $guard['adapter']['options'] = $options;
        }

        return $guard;
    }
}

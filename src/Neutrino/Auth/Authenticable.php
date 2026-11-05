<?php

declare(strict_types=1);

namespace Neutrino\Auth;

use Neutrino\Constants\Services;
use Phalcon\Contracts\Auth\RememberToken as RememberTokenContract;

/**
 * Implements {@see \Neutrino\Interfaces\Auth\Authenticable} on a model.
 *
 * The remember-me token is stored hashed (SHA-256, bound to the user agent) in the `getRememberTokenName()`
 * column: one token per user, a new remember-me login replaces the previous one. To keep one token per device,
 * implement {@see \Phalcon\Contracts\Auth\AuthRemember} with a table of tokens instead.
 *
 * @mixin \Phalcon\Mvc\Model<mixed>
 */
trait Authenticable
{
    public function getAuthIdentifier(): int|string
    {
        $identifier = $this->{static::getAuthIdentifierName()};

        return is_int($identifier) ? $identifier : (is_scalar($identifier) ? (string) $identifier : '');
    }

    public function getAuthPassword(): string
    {
        $password = $this->{static::getAuthPasswordName()};

        return is_string($password) ? $password : '';
    }

    public function createRememberToken(string $token, ?string $userAgent = null): RememberTokenContract
    {
        // The model adapter of Phalcon does not give the user agent, which the session guard checks.
        $userAgent ??= $this->currentUserAgent();

        $this->{static::getRememberTokenName()} = self::hashRememberToken($token, $userAgent);
        $this->save();

        return new RememberToken($token, $userAgent, $this->forgetRememberToken(...));
    }

    /**
     * The token of the remember-me cookie, if it is the one stored for this user and for the current user agent.
     */
    public function getRememberToken(string $token): ?RememberTokenContract
    {
        $stored = $this->{static::getRememberTokenName()};
        $userAgent = $this->currentUserAgent();

        if (!is_string($stored) || $stored === '' || !hash_equals($stored, self::hashRememberToken($token, $userAgent))) {
            return null;
        }

        return new RememberToken($token, $userAgent, $this->forgetRememberToken(...));
    }

    /**
     * Revokes the remember-me token.
     */
    public function forgetRememberToken(): bool
    {
        $this->{static::getRememberTokenName()} = null;

        return $this->save();
    }

    /**
     * Column (and credential) holding the identifier stored in the session and in the remember-me cookie.
     */
    public static function getAuthIdentifierName(): string
    {
        return 'email';
    }

    public static function getAuthPasswordName(): string
    {
        return 'password';
    }

    public static function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    private static function hashRememberToken(string $token, string $userAgent): string
    {
        return hash('sha256', $token . "\0" . $userAgent);
    }

    private function currentUserAgent(): string
    {
        $di = $this->getDI();

        if (!$di->has(Services::REQUEST)) {
            return '';
        }

        /** @var \Phalcon\Http\RequestInterface $request */
        $request = $di->getShared(Services::REQUEST);

        return (string) $request->getUserAgent();
    }
}

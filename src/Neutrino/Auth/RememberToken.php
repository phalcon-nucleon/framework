<?php

declare(strict_types=1);

namespace Neutrino\Auth;

use Closure;
use Phalcon\Contracts\Auth\RememberToken as RememberTokenContract;

/**
 * A remember-me token of {@see Authenticable}: the clear token of the cookie, never stored.
 */
final readonly class RememberToken implements RememberTokenContract
{
    /**
     * @param Closure(): bool $delete Revokes the token
     */
    public function __construct(private string $token, private string $userAgent, private Closure $delete) {}

    public function delete(): bool
    {
        return ($this->delete)();
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }
}

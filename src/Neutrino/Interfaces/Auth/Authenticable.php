<?php

declare(strict_types=1);

namespace Neutrino\Interfaces\Auth;

use Phalcon\Contracts\Auth\AuthRemember;
use Phalcon\Contracts\Auth\AuthUser;

/**
 * A user of `Phalcon\Auth`, with the remember-me. Implemented by the {@see \Neutrino\Auth\Authenticable} trait.
 */
interface Authenticable extends AuthUser, AuthRemember
{
    /**
     * Column (and credential) holding the identifier stored in the session and in the remember-me cookie.
     */
    public static function getAuthIdentifierName(): string;

    public static function getAuthPasswordName(): string;

    /**
     * Column holding the remember-me token, hashed.
     */
    public static function getRememberTokenName(): string;
}

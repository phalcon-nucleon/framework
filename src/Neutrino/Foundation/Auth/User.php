<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Auth;

use Neutrino\Auth\Authenticable;
use Neutrino\Interfaces\Auth\Authenticable as AuthenticableInterface;
use Neutrino\Model;

/**
 * A user model for `Phalcon\Auth`: columns `email` (identifier), `password` (hash) and `remember_token`.
 */
class User extends Model implements AuthenticableInterface
{
    use Authenticable;
}

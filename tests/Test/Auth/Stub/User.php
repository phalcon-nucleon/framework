<?php

declare(strict_types=1);

namespace Test\Auth\Stub;

use Neutrino\Auth\Authenticable;
use Neutrino\Interfaces\Auth\Authenticable as AuthenticableInterface;
use Phalcon\Mvc\Model;

/**
 * @property int         $id
 * @property string      $email
 * @property string      $password
 * @property string|null $remember_token
 */
final class User extends Model implements AuthenticableInterface
{
    use Authenticable;

    public function initialize(): void
    {
        $this->setSource('users');
    }
}

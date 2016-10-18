<?php

namespace Luxury\Foundation\Auth;

use Luxury\Auth\Authenticable;
use Luxury\Interfaces\Auth\Authenticable as AuthenticableInterface;
use Phalcon\Mvc\Model;

/**
 * Class User
 *
 * @package Luxury\Auth\Model
 */
class User extends Model implements AuthenticableInterface
{
    use Authenticable;
}

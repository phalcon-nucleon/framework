<?php

namespace Luxury\Interfaces\Auth;

use Phalcon\Acl\Role;

/**
 * interface Authorizable
 *
 * @package Luxury\Auth
 */
interface Authorizable
{
    /**
     * @return Role
     */
    public function getRole();
}

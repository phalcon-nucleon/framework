<?php

namespace Luxury\Auth;

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
    public function getRole() : Role;
}

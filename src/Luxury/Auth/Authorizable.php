<?php

namespace Luxury\Auth;

use Phalcon\Acl\RoleInterface;

/**
 * interface Authorizable
 *
 * @package Luxury\Auth
 */
interface Authorizable
{
    /**
     * @return RoleInterface
     */
    public function getRole() : RoleInterface;

    /**
     * @param RoleInterface $role
     *
     * @return Authorizable
     */
    public function setRole(RoleInterface $role) : Authorizable;
}

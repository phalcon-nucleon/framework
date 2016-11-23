<?php

namespace Neutrino\Interfaces\Auth;

use Phalcon\Acl\Role;

/**
 * interface Authorizable
 *
 *  @package Neutrino\Auth
 */
interface Authorizable
{
    /**
     * @return Role
     */
    public function getRole();
}

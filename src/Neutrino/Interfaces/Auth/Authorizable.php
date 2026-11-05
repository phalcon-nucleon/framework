<?php

declare(strict_types=1);

namespace Neutrino\Interfaces\Auth;

use Phalcon\Acl\RoleInterface;

interface Authorizable
{
    public function getRole(): RoleInterface;
}

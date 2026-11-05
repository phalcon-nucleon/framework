<?php

declare(strict_types=1);

namespace Neutrino\Constants\Events;

/**
 * Class Acl
 *
 * Contains a list of events related to the area 'acl'
 *
 *  @package Neutrino\Constants\Events
 */
final class Acl
{
    public const string BEFORE_CHECK_ACCESS = 'acl:beforeCheckAccess';
    public const string AFTER_CHECK_ACCESS  = 'acl:afterCheckAccess';
}

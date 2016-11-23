<?php

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
    const BEFORE_CHECK_ACCESS = 'acl:beforeCheckAccess';
    const AFTER_CHECK_ACCESS  = 'acl:afterCheckAccess';
}

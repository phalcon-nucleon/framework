<?php

namespace Luxury\Constants\Events;

/**
 * Class Acl
 *
 * @package Luxury\Constants\Events
 *
 * Contains a list of events related to the area 'acl'
 */
final class Acl
{
    const BEFORE_CHECK_ACCESS = 'acl:beforeCheckAccess';
    const AFTER_CHECK_ACCESS  = 'acl:afterCheckAccess';
}

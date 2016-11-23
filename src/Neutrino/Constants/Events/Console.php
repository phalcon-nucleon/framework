<?php

namespace Neutrino\Constants\Events;

/**
 * Class Console
 *
 * Contains a list of events related to the area 'console'
 *
 *  @package Neutrino\Constants\Events
 */
final class Console
{
    const BEFORE_START_MODULE = 'console:beforeStartModule';
    const AFTER_START_MODULE  = 'console:afterStartModule';
    const BEFORE_HANDLE_TASK  = 'console:beforeHandleTask';
    const AFTER_HANDLE_TASK   = 'console:afterHandleTask';
}

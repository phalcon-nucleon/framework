<?php

declare(strict_types=1);

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
    public const string BEFORE_START_MODULE = 'console:beforeStartModule';
    public const string AFTER_START_MODULE  = 'console:afterStartModule';
    public const string BEFORE_HANDLE_TASK  = 'console:beforeHandleTask';
    public const string AFTER_HANDLE_TASK   = 'console:afterHandleTask';
}

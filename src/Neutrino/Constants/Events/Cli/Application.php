<?php

declare(strict_types=1);

namespace Neutrino\Constants\Events\Cli;

/**
 * Class Application
 *
 * Contains a list of events related to the area 'application'
 *
 *  @package Neutrino\Constants\Events
 */
final class Application
{
    public const string BOOT                  = 'console:boot';
    public const string BEFORE_START_MODULE   = 'console:beforeStartModule';
    public const string AFTER_START_MODULE    = 'console:afterStartModule';
    public const string BEFORE_HANDLE         = 'console:beforeHandleTask';
    public const string AFTER_HANDLE          = 'console:afterHandleTask';
}

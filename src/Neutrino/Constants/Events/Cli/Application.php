<?php

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
    const BOOT                  = 'console:boot';
    const BEFORE_START_MODULE   = 'console:beforeStartModule';
    const AFTER_START_MODULE    = 'console:afterStartModule';
    const BEFORE_HANDLE         = 'console:beforeHandleTask';
    const AFTER_HANDLE          = 'console:afterHandleTask';
}

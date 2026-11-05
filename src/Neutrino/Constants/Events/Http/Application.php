<?php

declare(strict_types=1);

namespace Neutrino\Constants\Events\Http;

/**
 * Class Application
 *
 * Contains a list of events related to the area 'application'
 *
 *  @package Neutrino\Constants\Events
 */
final class Application
{
    public const string BOOT                  = 'application:boot';
    public const string BEFORE_START_MODULE   = 'application:beforeStartModule';
    public const string AFTER_START_MODULE    = 'application:afterStartModule';
    public const string BEFORE_HANDLE         = 'application:beforeHandleRequest';
    public const string AFTER_HANDLE          = 'application:afterHandleRequest';
    public const string VIEW_RENDER           = 'application:viewRender';
    public const string BEFORE_SEND_RESPONSE  = 'application:beforeSendResponse';
}

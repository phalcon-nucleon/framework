<?php

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
    const BOOT                  = 'application:boot';
    const BEFORE_START_MODULE   = 'application:beforeStartModule';
    const AFTER_START_MODULE    = 'application:afterStartModule';
    const BEFORE_HANDLE         = 'application:beforeHandleRequest';
    const AFTER_HANDLE          = 'application:afterHandleRequest';
    const VIEW_RENDER           = 'application:viewRender';
    const BEFORE_SEND_RESPONSE  = 'application:beforeSendResponse';
}

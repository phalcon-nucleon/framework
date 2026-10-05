<?php

declare(strict_types=1);

namespace Neutrino\Constants;

/**
 * Class Services
 *
 * List of Services available in Di
 *
 *  @package Neutrino\Constants
 */
final class Services
{
    public const string ACL                 = 'acl';
    public const string ANNOTATIONS         = 'annotations';
    public const string APP                 = 'application';
    public const string ASSETS              = 'assets';
    public const string AUTH                = 'auth';
    public const string CACHE               = 'cache';
    public const string CONFIG              = 'config';
    public const string COOKIES             = 'cookies';
    public const string CRYPT               = 'crypt';
    public const string DB                  = 'db';
    public const string DISPATCHER          = 'dispatcher';
    public const string ESCAPER             = 'escaper';
    public const string EVENTS_MANAGER      = 'eventsManager';
    public const string FILTER              = 'filter';
    public const string FLASH               = 'flash';
    public const string FLASH_SESSION       = 'flashSession';
    public const string HTTP_CLIENT         = 'httpClient';
    public const string LOGGER              = 'logger';
    public const string MICRO_ROUTER        = 'micro.router';
    public const string MODELS_MANAGER      = 'modelsManager';
    public const string MODELS_METADATA     = 'modelsMetadata';
    public const string TRANSACTION_MANAGER = 'transactionManager';
    public const string ROUTER              = 'router';
    public const string RESPONSE            = 'response';
    public const string REQUEST             = 'request';
    public const string SESSION             = 'session';
    public const string SESSION_BAG         = 'sessionBag';
    public const string SECURITY            = 'security';
    public const string TAG                 = 'tag';
    public const string URL                 = 'url';
    public const string VIEW                = 'view';
}

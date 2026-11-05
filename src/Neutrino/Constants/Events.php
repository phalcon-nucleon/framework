<?php

declare(strict_types=1);

namespace Neutrino\Constants;

/**
 * Class Events
 *
 * List of event Space listenable
 *
 *  @package Neutrino\Constants
 */
final class Events
{
    public const string DISPATCH           = 'dispatch';
    public const string LOADER             = 'loader';
    public const string ACL                = 'acl';
    public const string CONSOLE            = 'console';
    public const string DB                 = 'db';
    public const string APPLICATION        = 'application';
    public const string MICRO              = 'micro';
    public const string MODEL              = 'model';
    public const string VIEW               = 'view';
    public const string MODELS_MANAGER     = 'modelsManager';
    public const string ROUTER             = 'router';
    public const string DI                 = 'di';
    public const string KERNEL             = 'kernel';
}

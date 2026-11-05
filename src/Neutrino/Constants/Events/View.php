<?php

declare(strict_types=1);

namespace Neutrino\Constants\Events;

/**
 * Class View
 *
 * Contains a list of events related to the area 'view'
 *
 *  @package Neutrino\Constants\Events
 */
final class View
{
    public const string BEFORE_RENDER_VIEW = 'view:beforeRenderView';
    public const string AFTER_RENDER_VIEW  = 'view:afterRenderView';
    public const string NOT_FOUND_VIEW     = 'view:notFoundView';
    public const string BEFORE_RENDER      = 'view:beforeRender';
    public const string AFTER_RENDER       = 'view:afterRender';
    public const string BEFORE_COMPILE     = 'view:beforeCompile';
    public const string AFTER_COMPILE      = 'view:afterCompile';
}

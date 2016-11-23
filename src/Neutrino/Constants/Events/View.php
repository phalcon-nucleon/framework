<?php

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
    const BEFORE_RENDER_VIEW = 'view:beforeRenderView';
    const AFTER_RENDER_VIEW  = 'view:afterRenderView';
    const NOT_FOUND_VIEW     = 'view:notFoundView';
    const BEFORE_RENDER      = 'view:beforeRender';
    const AFTER_RENDER       = 'view:afterRender';
}

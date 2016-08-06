<?php

namespace Luxury\Constants\Events;

/**
 * Class View
 *
 * @package Luxury\Constants\Events
 *
 * Contains a list of events related to the area 'view'
 */
final class View
{
    const BEFORE_RENDER_VIEW = 'view:beforeRenderView';
    const AFTER_RENDER_VIEW  = 'view:afterRenderView';
    const NOT_FOUND_VIEW     = 'view:notFoundView';
    const BEFORE_RENDER      = 'view:beforeRender';
    const AFTER_RENDER       = 'view:afterRender';
}

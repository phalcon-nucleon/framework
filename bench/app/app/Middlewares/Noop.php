<?php

namespace Bench\Middlewares;

use Neutrino\Foundation\Middleware\Controller;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Phalcon\Events\Event;

/**
 * Route middleware doing nothing: measures the cost of the middleware machinery.
 * Written for both the 1.3 and the 2.0 API (no types).
 */
class Noop extends Controller implements BeforeInterface
{
    public function before(Event $event, $source, $data = null)
    {
        return true;
    }
}

<?php

namespace Luxury\Http\Middleware;

use Luxury\Constants\Events as EventSpaces;
use Luxury\Foundation\Middleware\Controller as ControllerMiddleware;
use Luxury\Middleware\BeforeMiddleware;
use Luxury\Support\Facades\Log;

/**
 * Class Throttle
 *
 * @package     Luxury\Middleware
 */
class Throttle extends ControllerMiddleware implements BeforeMiddleware
{
    /**
     * Called before the execution of handler
     *
     * @param \Phalcon\Events\Event|mixed $event
     * @param \Phalcon\Dispatcher|mixed   $source
     * @param mixed|null                  $data
     *
     * @throws \Exception
     * @return bool
     */
    public function before($event, $source, $data = null)
    {
        // TODO Implement Rate Limit
        Log::debug(__METHOD__);
    }
}

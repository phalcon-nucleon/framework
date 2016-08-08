<?php

namespace Stub;

use Luxury\Foundation\Controller;
use Luxury\Http\Middleware\Throttle as ThrottleMiddleware;

/**
 * Class StubThrottledController
 *
 * @package     Stub
 */
class StubThrottledController extends Controller
{
    /**
     * Event called on controller construction
     *
     * Register middleware here.
     */
    protected function onConstruct()
    {
        $this->middleware(new ThrottleMiddleware(10, 60));
    }

    public function indexAction()
    {
    }
}

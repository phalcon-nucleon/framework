<?php

namespace Test\Stub;

use Neutrino\Constants\Services;
use Neutrino\Http\Controller;
use Neutrino\Http\Middleware\ThrottleRequest as ThrottleMiddleware;

/**
 * Class StubThrottledController
 *
 * @package     Stub
 */
class StubthrottledController extends Controller
{
    /**
     * Event called on controller construction
     *
     * Register middleware here.
     */
    protected function onConstruct()
    {
        $this->middleware(ThrottleMiddleware::class, 10, 60)->only(['index']);
    }

    public function indexAction()
    {
        $this->getDI()->getShared(Services::RESPONSE)->setStatusCode(200);
    }

    public function throttledAction()
    {
        $this->getDI()->getShared(Services::RESPONSE)->setStatusCode(200);
    }
}

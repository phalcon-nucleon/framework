<?php


namespace Stub;

use Luxury\Auth\Middleware\Acl as AclMiddleware;
use Luxury\Foundation\Controller;

class StubprivateController extends Controller
{
    /**
     * Event called on controller construction
     *
     * Register middleware here.
     */
    protected function onConstruct()
    {
        $this->middleware(AclMiddleware::create(self::class, [
            'index',
            'private'
        ])->allow('guest', ['index']));
    }

    public function indexAction()
    {

    }

    public function privateAction()
    {

    }
}

<?php

namespace Test\Stub;

use Luxury\Foundation\Controller;

/**
 * Class StubController
 *
 * @package     Stub
 */
class StubController extends Controller
{
    /**
     * @var array
     */
    public static $middlewares = [];

    /**
     * Event called on controller construction
     *
     * Register middleware here.
     */
    protected function onConstruct()
    {
        foreach (self::$middlewares as $middleware) {
            foreach ($middleware['params'] as $key => $method) {
                $this->middleware($middleware['middleware']->$key($method));
            }
        }
    }

    public function indexAction()
    {
    }

    public function returnAction()
    {
        return __METHOD__;
    }

    public function redirectAction()
    {
        $this->response->redirect('/');
    }

    public function forwardedAction()
    {
        $this->dispatcher->forward([
            "controller" => "Stub",
            "action"     => "index",
        ]);
    }
}

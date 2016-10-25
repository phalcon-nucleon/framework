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

    public function dataAction()
    {
        $method = $this->request->getMethod();
        $queries = [];
        switch ($method) {
            case 'GET':
            case 'PATCH':
                $queries = $_GET;
                break;
            case 'POST':
            case 'PUT':
                $queries = $_POST;
        }

        return json_encode([
            'method' => $method,
            'queries' => $queries
        ]);
    }

    public function redirectAction()
    {
        $this->response->redirect('/');
    }

    public function forwardedAction()
    {
        $this->dispatcher->forward([
            "controller" => "Stub",
            "action" => "index",
        ]);
    }
}

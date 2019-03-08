<?php

namespace Test\Micro;

use Neutrino\Constants\Events;
use Neutrino\Constants\Services;
use Neutrino\Interfaces\Middleware\AfterInterface;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Neutrino\Micro\Router;
use Phalcon\Events\Event;
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Micro\Collection;
use Test\TestCase\TestCase;

class MicroRouterTest extends TestCase
{
    use MicroTestCase;

    public function dataRegisteringClosureHttpMethod()
    {
        return [
            ['test.get', 'get', 'test.get', function () {
                /** @var \Neutrino\Foundation\Micro\Kernel $this */
                $this->response->setContent('test.get');

                return $this->response;
            }],
            ['test.post', 'post', 'test.post', function () {
                /** @var \Neutrino\Foundation\Micro\Kernel $this */
                $this->response->setContent('test.post');

                return $this->response;
            }],
            ['test.delete', 'delete', 'test.delete', function () {
                /** @var \Neutrino\Foundation\Micro\Kernel $this */
                $this->response->setContent('test.delete');

                return $this->response;
            }],
            ['test.put', 'put', 'test.put', function () {
                /** @var \Neutrino\Foundation\Micro\Kernel $this */
                $this->response->setContent('test.put');

                return $this->response;
            }],
            ['test.patch', 'patch', 'test.patch', function () {
                /** @var \Neutrino\Foundation\Micro\Kernel $this */
                $this->response->setContent('test.patch');

                return $this->response;
            }],
            ['test.options', 'options', 'test.options', function () {
                /** @var \Neutrino\Foundation\Micro\Kernel $this */
                $this->response->setContent('test.options');

                return $this->response;
            }],
            ['test.head', 'head', 'test.head', function () {
                /** @var \Neutrino\Foundation\Micro\Kernel $this */
                $this->response->setContent('test.head');

                return $this->response;
            }],
        ];
    }

    /**
     * @dataProvider dataRegisteringClosureHttpMethod
     *
     * @param $expected
     * @param $httpMethod
     * @param $path
     * @param $handler
     */
    public function testRegisteringClosureHttpMethod($expected, $httpMethod, $path, $handler)
    {
        /** @var Router $router */
        $router = $this->app->{Services::MICRO_ROUTER};

        $router->{'add' . ucfirst($httpMethod)}($path, $handler);

        $this->dispatch($path, strtoupper($httpMethod), [], $output);

        $this->assertEquals($output, $this->getContent());
        $this->assertEquals($expected, $this->getContent());
        $this->assertEquals($expected, $output);
    }

    public function testRegisteringControllerHttpMethod()
    {
        StubMicroHttpMiddleware::$call = null;
        StubMicroHttpMiddleware::$return = null;

        /** @var Router $router */
        $router = $this->app->{Services::MICRO_ROUTER};

        $router->addGet('/micro/index', [
            'controller' => StubMicroController::class,
            'action' => 'index',
            'middlewares' => [StubMicroHttpMiddleware::class]
        ]);

        $this->dispatch('/micro/index');

        $this->assertEquals(json_encode(['foo' => 'bar']), $this->getContent());
        $this->assertEquals([
            [
                'method' => 'before',
                'event'  => new Event(Events\Micro::BEFORE_EXECUTE_ROUTE, $this->app),
                'src'    => $this->app,
                'data'   => null
            ],
            [
                'method' => 'after',
                'event'  => new Event(Events\Micro::AFTER_EXECUTE_ROUTE, $this->app),
                'src'    => $this->app,
                'data'   => null
            ]
        ], StubMicroHttpMiddleware::$call);
    }

    public function testRegisteringHttpControllerMiddlewareReturnFalse()
    {
        StubMicroHttpMiddleware::$call = null;
        StubMicroHttpMiddleware::$return = false;

        /** @var Router $router */
        $router = $this->app->{Services::MICRO_ROUTER};
        $router->addGet('/micro/index', [
            'controller' => StubMicroController::class,
            'action' => 'index',
            'middlewares' => [StubMicroHttpMiddleware::class]
        ]);

        $this->dispatch('/micro/index');

        $this->assertEquals('', $this->getContent());
        $this->assertEquals([
            [
                'method' => 'before',
                'event'  => new Event(Events\Micro::BEFORE_EXECUTE_ROUTE, $this->app),
                'src'    => $this->app,
                'data'   => null
            ]
        ], StubMicroHttpMiddleware::$call);
    }

    public function dataTryRegisteringUnsupportedHttpMethod()
    {
        return [
            ['purge'],
            ['connect'],
            ['trace'],
        ];
    }

    /**
     * @dataProvider                    dataTryRegisteringUnsupportedHttpMethod
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp  /Neutrino\\Micro\\Router::add\w+: Micro Application doesn't support HTTP \w+ method\./
     *
     * @param $httpMethod
     */
    public function testTryRegisteringUnsupportedHttpMethod($httpMethod)
    {

        /** @var Router $router */
        $router = $this->app->{Services::MICRO_ROUTER};

        $router->{'add' . ucfirst($httpMethod)}('', function () {
        });
    }

    public function dataUnsupportedMethods()
    {
        return [
            ['setDefaultModule'],
            ['setDefaultController'],
            ['setDefaultAction'],
            ['setDefaults'],
            ['clear'],
            ['getModuleName'],
        ];
    }

    /**
     * @dataProvider dataUnsupportedMethods
     * @expectedException \RuntimeException
     */
    public function testUnsupportedMethods($method)
    {
        /** @var Router $router */
        $router = $this->app->{Services::MICRO_ROUTER};

        $router->$method([], [], []);
    }

    public function testMethodToRouter()
    {
        $methods = [
            'handle'            => ['uri'],
            'getNamespaceName'  => [],
            'getControllerName' => [],
            'getActionName'     => [],
            'getParams'         => [],
            'getMatchedRoute'   => [],
            'getMatches'        => [],
            'wasMatched'        => [],
            'getRoutes'         => [],
            'getRouteById'      => [1],
            'getRouteByName'    => ['name'],
        ];

        $router = $this->mockService(Services::ROUTER, \Phalcon\Mvc\Router::class, true);

        $mrouter = new Router();

        foreach ($methods as $method => $params) {
            $router
                ->expects($this->once())
                ->method($method)
                ->with(...$params);

            $mrouter->$method(...$params);
        }
    }

    public function testMethodsToApplication()
    {
        $methods = [
            'mount'    => [new Collection()],
            'notFound' => ['handler']
        ];

        $application = $this->mockService(Services::APP, \Phalcon\Mvc\Micro::class, true);

        $mrouter = new Router();

        foreach ($methods as $method => $params) {
            $application
                ->expects($this->once())
                ->method($method)
                ->with(...$params);

            $mrouter->$method(...$params);
        }
    }
}

class StubMicroController extends Controller
{
    public function index()
    {
        return $this->response->setJsonContent(['foo' => 'bar']);
    }
}

class StubMicroHttpMiddleware extends \Neutrino\Foundation\Middleware\Controller implements BeforeInterface, AfterInterface
{
    public static $call;

    public static $return;

    public function before(Event $event, $source, $data = null)
    {
        self::$call[] = [
            'method' => 'before',
            'event'  => $event,
            'src'    => $source,
            'data'   => $data
        ];

        return self::$return;
    }

    public function after(Event $event, $source, $data = null)
    {
        self::$call[] = [
            'method' => 'after',
            'event'  => $event,
            'src'    => $source,
            'data'   => $data
        ];
    }
}
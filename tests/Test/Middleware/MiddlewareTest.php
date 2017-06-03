<?php

namespace Test\Middleware;

use Fake\Kernels\Http\Controllers\StubController;
use Test\Middleware\Stub\ApplicationMiddlewareStub;
use Test\Middleware\Stub\ControllerForwardMiddlewareStub;
use Test\Middleware\Stub\ControllerMiddlewareStub;
use Test\Middleware\Stub\DispatchMiddlewareStub;
use Test\TestCase\TestCase;
use Test\TestCase\TestListenize;

/**
 * Class MiddlewareTest
 *
 * @package Test
 */
class MiddlewareTest extends TestCase
{
    public function testControllerMiddleware()
    {
        // GIVEN
        $middleware = new ControllerMiddlewareStub(StubController::class);

        $this->app->useImplicitView(false);

        $this->app->attach($middleware);

        // WHEN
        $this->dispatch('/');

        // THEN
        $this->assertFalse($middleware->hasView('init'));
        $this->assertTrue($middleware->hasView('before'));
        $this->assertTrue($middleware->hasView('after'));
        $this->assertTrue($middleware->hasView('finish'));

        $this->assertEquals(0, count($middleware->getView('init')));
        $this->assertEquals(1, count($middleware->getView('before')));
        $this->assertEquals(1, count($middleware->getView('after')));
        $this->assertEquals(1, count($middleware->getView('finish')));
    }

    public function setUp()
    {
        parent::setUp();

        StubController::$registerMiddlewares = [];
        StubController::$middlewares = [];
    }

    /**
     * @return array
     */
    public function dataFiltereControllerMiddleware()
    {
        return [
            'only.indexAction'   => ['only', ['index'], 0, 1, 1, 1],
            'except.indexAction' => ['except', ['index'], 0, 0, 0, 0],
            'only.index'         => ['only', ['wrong'], 0, 0, 0, 0],
            'except.index'       => ['except', ['wrong'], 0, 1, 1, 1],
        ];
    }

    /**
     * @dataProvider dataFiltereControllerMiddleware
     *
     * @param $filter
     * @param $methods
     * @param $init
     * @param $before
     * @param $after
     * @param $finish
     */
    public function testFilteredControllerMiddleware(
        $filter,
        $methods,
        $init,
        $before,
        $after,
        $finish
    ) {
        // GIVEN
        StubController::$middlewares[] = [
            'middleware' => ControllerMiddlewareStub::class,
            'params'     => [$filter => $methods]
        ];

        $this->app->useImplicitView(false);

        // WHEN
        $this->dispatch('/');

        /** @var TestListenize $middleware */
        $middleware = StubController::$registerMiddlewares[count(StubController::$middlewares) - 1];

        // THEN
        $this->assertEquals($init, count($middleware->getView('init')));
        $this->assertEquals($before, count($middleware->getView('before')));
        $this->assertEquals($after, count($middleware->getView('after')));
        $this->assertEquals($finish, count($middleware->getView('finish')));
    }

    public function testDispatchMiddleware()
    {
        // GIVEN
        $middleware = new DispatchMiddlewareStub();

        $this->app->useImplicitView(false);

        $this->app->attach($middleware);

        // WHEN
        $this->dispatch('/');

        // THEN
        $this->assertTrue($middleware->hasView('init'));
        $this->assertTrue($middleware->hasView('before'));
        $this->assertTrue($middleware->hasView('after'));
        $this->assertTrue($middleware->hasView('finish'));

        $this->assertEquals(1, count($middleware->getView('init')));
        $this->assertEquals(1, count($middleware->getView('before')));
        $this->assertEquals(1, count($middleware->getView('after')));
        $this->assertEquals(1, count($middleware->getView('finish')));
    }

    public function testApplicationMiddleware()
    {
        // GIVEN
        $middleware = new ApplicationMiddlewareStub();

        $this->app->useImplicitView(false);

        $this->app->attach($middleware);

        // WHEN
        $this->dispatch('/');

        // THEN
        $this->assertTrue($middleware->hasView('init'));
        $this->assertTrue($middleware->hasView('before'));
        $this->assertTrue($middleware->hasView('after'));
        $this->assertTrue($middleware->hasView('finish'));

        $this->assertEquals(1, count($middleware->getView('init')));
        $this->assertEquals(1, count($middleware->getView('before')));
        $this->assertEquals(1, count($middleware->getView('after')));
        $this->assertEquals(1, count($middleware->getView('finish')));
    }

    public function testForwarded()
    {
        StubController::$middlewares[] = [
            'middleware' => ControllerMiddlewareStub::class,
            'params'     => ['only' => ['forwarded']]
        ];

        StubController::$middlewares[] = [
            'middleware' => ControllerMiddlewareStub::class,
            'params'     => ['only' => ['index']]
        ];

        $this->dispatch('/forwarded');

        /** @var TestListenize $middleware */
        $middleware = StubController::$registerMiddlewares[0];

        $this->assertEquals(0, count($middleware->getView('init')));
        $this->assertEquals(1, count($middleware->getView('before')));
        $this->assertEquals(0, count($middleware->getView('after')));
        $this->assertEquals(0, count($middleware->getView('finish')));

        $middleware = StubController::$registerMiddlewares[1];

        $this->assertEquals(0, count($middleware->getView('init')));
        $this->assertEquals(1, count($middleware->getView('before')));
        $this->assertEquals(1, count($middleware->getView('after')));
        $this->assertEquals(1, count($middleware->getView('finish')));
    }

    public function testForwardedByMiddleware()
    {
        StubController::$middlewares[] = [
            'middleware' => ControllerForwardMiddlewareStub::class,
            'params'     => ['only' => ['return']],
            'construct'  => ['Stub', 'index']
        ];

        StubController::$middlewares[] = [
            'middleware' => ControllerMiddlewareStub::class,
            'params'     => ['only' => ['index']]
        ];

        $this->dispatch('/return');

        /** @var TestListenize $middleware */
        $middleware = StubController::$registerMiddlewares[0];

        $this->assertEquals(0, count($middleware->getView('init')));
        $this->assertEquals(1, count($middleware->getView('before')));
        $this->assertEquals(0, count($middleware->getView('after')));
        $this->assertEquals(0, count($middleware->getView('finish')));

        $middleware = StubController::$registerMiddlewares[1];

        $this->assertEquals(0, count($middleware->getView('init')));
        $this->assertEquals(1, count($middleware->getView('before')));
        $this->assertEquals(1, count($middleware->getView('after')));
        $this->assertEquals(1, count($middleware->getView('finish')));
    }
}

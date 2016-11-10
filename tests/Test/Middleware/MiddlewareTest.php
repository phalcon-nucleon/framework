<?php

namespace Test\Middleware;

use Test\Middleware\Stub\ApplicationMiddlewareStub;
use Test\Middleware\Stub\ControllerMiddlewareStub;
use Test\Middleware\Stub\DispatchMiddlewareStub;
use Test\Stub\StubController;
use Test\TestCase\TestCase;

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
            'only.indexAction'   => ['only', ['indexAction'], 0, 1, 1, 1],
            'except.indexAction' => ['except', ['indexAction'], 0, 0, 0, 0],
            'only.index'         => ['only', ['index'], 0, 0, 0, 0],
            'except.index'       => ['except', ['index'], 0, 1, 1, 1],
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
}

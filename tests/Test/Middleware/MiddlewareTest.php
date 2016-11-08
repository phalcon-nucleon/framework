<?php

namespace Test\Middleware;

use Luxury\Foundation\Middleware\Application as ApplicationMiddleware;
use Luxury\Foundation\Middleware\Controller as ControllerMiddleware;
use Luxury\Foundation\Middleware\Disptacher as DisptacherMiddleware;
use Luxury\Interfaces\Middleware\AfterInterface;
use Luxury\Interfaces\Middleware\BeforeInterface;
use Luxury\Interfaces\Middleware\FinishInterface;
use Luxury\Interfaces\Middleware\InitInterface;
use Test\Stub\StubController;
use Test\TestCase\TestCase;
use Test\TestCase\TestListenable;
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
        $middleware = new TestControllerMiddlewareStub();

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
        $middleware = new TestControllerMiddlewareStub();

        StubController::$middlewares[] = [
            'middleware' => $middleware,
            'params'     => [$filter => $methods]
        ];

        $this->app->useImplicitView(false);

        // WHEN
        $this->dispatch('/');

        // THEN
        $this->assertEquals($init, count($middleware->getView('init')));
        $this->assertEquals($before, count($middleware->getView('before')));
        $this->assertEquals($after, count($middleware->getView('after')));
        $this->assertEquals($finish, count($middleware->getView('finish')));
    }

    public function testDispatchMiddleware()
    {
        // GIVEN
        $middleware = new TestDispatchMiddlewareStub();

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
        $middleware = new TestApplicationMiddlewareStub();

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

class TestControllerMiddlewareStub extends ControllerMiddleware implements
    TestListenable,
    BeforeInterface,
    AfterInterface,
    FinishInterface
{
    use TestListenize, Middlewarize;
}

class TestDispatchMiddlewareStub extends DisptacherMiddleware implements
    TestListenable,
    InitInterface,
    BeforeInterface,
    AfterInterface,
    FinishInterface
{
    use TestListenize, Middlewarize;
}

class TestApplicationMiddlewareStub extends ApplicationMiddleware implements
    TestListenable,
    InitInterface,
    BeforeInterface,
    AfterInterface,
    FinishInterface
{
    use TestListenize, Middlewarize;
}

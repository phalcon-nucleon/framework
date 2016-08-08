<?php

namespace Middleware;

use Luxury\Constants\Events;
use Luxury\Foundation\Middleware\Application as ApplicationMiddleware;
use Luxury\Foundation\Middleware\Controller as ControllerMiddleware;
use Luxury\Foundation\Middleware\Disptacher as DisptacherMiddleware;
use Luxury\Middleware\AfterMiddleware;
use Luxury\Middleware\BeforeMiddleware;
use Luxury\Middleware\FinishMiddleware;
use Luxury\Middleware\InitMiddleware;
use TestCase\TestCase;
use TestCase\TestListenable;
use TestCase\TestListenize;

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

        $this->app->router->addGet('/', [
            'namespace' => 'Stub',
            'controller' => 'Stub',
            'action' => 'index'
        ]);

        $this->app->attach($middleware);

        // WHEN
        $this->app->handle('/');

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

    public function testDispatchMiddleware()
    {
        // GIVEN
        $middleware = new TestDispatchMiddlewareStub();

        $this->app->useImplicitView(false);

        $this->app->router->addGet('/', [
            'namespace' => 'Stub',
            'controller' => 'Stub',
            'action' => 'index'
        ]);

        $this->app->attach($middleware);

        // WHEN
        $this->app->handle('/');

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
        
        $this->app->router->addGet('/', [
            'namespace' => 'Stub',
            'controller' => 'Stub',
            'action' => 'index'
        ]);

        $this->app->attach($middleware);

        // WHEN
        $this->app->handle('/');

        // THEN
        $this->assertFalse($middleware->hasView('init'));
        $this->assertTrue($middleware->hasView('before'));
        $this->assertTrue($middleware->hasView('after'));
        $this->assertFalse($middleware->hasView('finish'));

        $this->assertEquals(0, count($middleware->getView('init')));
        $this->assertEquals(1, count($middleware->getView('before')));
        $this->assertEquals(1, count($middleware->getView('after')));
        $this->assertEquals(0, count($middleware->getView('finish')));
    }
}

class TestControllerMiddlewareStub extends ControllerMiddleware implements
    TestListenable,
    BeforeMiddleware,
    AfterMiddleware,
    FinishMiddleware
{
    use TestListenize, Middlewarize;
}

class TestDispatchMiddlewareStub extends DisptacherMiddleware implements
    TestListenable,
    InitMiddleware,
    BeforeMiddleware,
    AfterMiddleware,
    FinishMiddleware
{
    use TestListenize, Middlewarize;
}

class TestApplicationMiddlewareStub extends ApplicationMiddleware implements
    TestListenable,
    BeforeMiddleware,
    AfterMiddleware
{
    use TestListenize, Middlewarize;
}

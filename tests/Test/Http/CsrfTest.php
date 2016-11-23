<?php

namespace Test\Http;

use Neutrino\Constants\Services;
use Neutrino\Http\Middleware\Csrf;
use Phalcon\Version;
use Test\Stub\StubController;
use Test\TestCase\TestCase;

class CsrfTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();

        StubController::$middlewares[] = [
            'middleware' => Csrf::class,
            'params'     => [
                'only' => ['index']
            ]
        ];
    }

    public function tearDown()
    {
        parent::tearDown();

        StubController::$middlewares = [];
        StubController::$registerMiddlewares = [];
    }

    /**
     * @expectedException \Neutrino\Exceptions\TokenMismatchException
     */
    public function testCsrfFail_Get()
    {
        $this->dispatch('/');
    }

    /**
     * @expectedException \Neutrino\Exceptions\TokenMismatchException
     */
    public function testCsrfFail_Post()
    {
        $this->dispatch('/', 'POST');
    }

    public function testCsrfOk_Get()
    {
        $security = $this->getDI()->getShared(Services::SECURITY);

        $this->dispatch('/', 'GET', [$security->getTokenKey() => $security->getToken()]);

        $this->assertTrue(true);
    }

    public function testCsrfOk_Post()
    {
        $security = $this->getDI()->getShared(Services::SECURITY);

        $this->dispatch('/', 'POST', [$security->getTokenKey() => $security->getToken()]);

        $this->assertTrue(true);
    }

    public function testCsrfOk_Ajax()
    {
        $security = $this->getDI()->getShared(Services::SECURITY);

        $_SERVER["HTTP_X_REQUESTED_WITH"]              = "XMLHttpRequest";
        $_SERVER['HTTP_X_CSRF_' . strtoupper($security->getTokenKey())] = $security->getToken();

        $this->dispatch('/', 'POST', []);

        $this->assertTrue(true);
    }

    /**
     * @expectedException \Neutrino\Exceptions\TokenMismatchException
     */
    public function testCsrfFail_Ajax()
    {
        $_SERVER["HTTP_X_REQUESTED_WITH"] = "XMLHttpRequest";

        $this->dispatch('/', 'POST', []);

        $this->assertTrue(true);
    }
}

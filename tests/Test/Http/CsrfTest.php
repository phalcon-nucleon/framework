<?php

namespace Test\Http;

use Luxury\Constants\Services;
use Luxury\Http\Middleware\Csrf;
use Test\Stub\StubController;
use Test\TestCase\TestCase;

class CsrfTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();

        StubController::$middlewares[] = [
            'middleware' => new Csrf(),
            'params' => [
                'only' => ['indexAction']
            ]
        ];
    }

    public function tearDown()
    {
        parent::tearDown();

        StubController::$middlewares = [];
    }

    /**
     * @expectedException \Luxury\Exceptions\TokenMismatchException
     */
    public function testCsrfFail_Get()
    {
        $this->dispatch('/');
    }

    /**
     * @expectedException \Luxury\Exceptions\TokenMismatchException
     */
    public function testCsrfFail_Post()
    {
        $this->dispatch('/', 'POST');
    }

    public function testCsrfOk_Get()
    {
        $security = $this->getDI()->getShared(Services::SECURITY);

        $this->dispatch('/', 'GET', [$security->getTokenKey() => $security->getToken()]);

        $this->assertTrue(TRUE);
    }

    public function testCsrfOk_Post()
    {
        $security = $this->getDI()->getShared(Services::SECURITY);

        $this->dispatch('/', 'POST', [$security->getTokenKey() => $security->getToken()]);

        $this->assertTrue(TRUE);
    }
}

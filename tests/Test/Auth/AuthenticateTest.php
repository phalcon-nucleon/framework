<?php

declare(strict_types=1);

namespace Test\Auth;

use Fake\Kernels\Http\Controllers;
use Neutrino\Auth\Middleware\Authenticate;
use Neutrino\Constants\Services;
use Neutrino\Http\Standards\StatusCode;
use Phalcon\Auth\Manager;
use Test\TestCase\TestCase;

final class AuthenticateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->useImplicitView(false);
        $this->app->router->addGet('/private', ['namespace' => Controllers::class, 'controller' => 'Stub', 'action' => 'index', 'middleware' => Authenticate::class]);
        $this->app->router->addGet('/redirect', ['namespace' => Controllers::class, 'controller' => 'Stub', 'action' => 'index', 'middleware' => [Authenticate::class => '/login']]);
    }

    public function testAuthenticated(): void
    {
        $this->auth(true);

        $this->dispatch('/private');

        $this->assertNull($this->getDI()->getShared(Services::RESPONSE)->getStatusCode());
    }

    public function testGuestGetsA401(): void
    {
        $this->auth(false);

        $this->dispatch('/private');

        $this->assertResponseCode(StatusCode::UNAUTHORIZED);
    }

    public function testGuestIsRedirected(): void
    {
        $this->auth(false);

        $this->dispatch('/redirect');

        $this->assertRedirectTo('/login');
    }

    public function testServiceIsBuiltOnlyForTheRoute(): void
    {
        $this->app->router->addGet('/public', ['namespace' => Controllers::class, 'controller' => 'Stub', 'action' => 'index']);
        $this->getDI()->setShared(Services::AUTH, function () {
            throw new \LogicException('built');
        });

        $this->dispatch('/public');

        $this->assertFalse($this->getDI()->getService(Services::AUTH)->isResolved());
    }

    private function auth(bool $check): void
    {
        $auth = $this->createMock(Manager::class);
        $auth->method('check')->willReturn($check);

        $this->getDI()->setShared(Services::AUTH, $auth);
    }
}

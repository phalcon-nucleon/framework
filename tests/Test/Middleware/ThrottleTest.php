<?php

declare(strict_types=1);

namespace Test\Middleware;

use Fake\Kernels\Http\Controllers;
use Fake\Kernels\Http\Controllers\StubController;
use Neutrino\Auth\Middleware\ThrottleLogin;
use Neutrino\Constants\Services;
use Neutrino\Http\Middleware\ThrottleRequest;
use Neutrino\Http\Standards\StatusCode;
use Neutrino\Middleware\Throttle;
use Phalcon\Http\Response;
use RuntimeException;
use Test\TestCase\TestCase;
use Test\TestCase\UseCaches;

final class ThrottleTest extends TestCase
{
    use UseCaches;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->useImplicitView(false);
        $this->app->router->addGet('/', ['namespace' => Controllers::class, 'controller' => 'Stubthrottled', 'action' => 'index']);
        $this->app->router->addGet('/not-throttled', ['namespace' => Controllers::class, 'controller' => 'Stubthrottled', 'action' => 'throttled']);
        $this->app->router->addGet('/route', ['namespace' => Controllers::class, 'controller' => 'Stub', 'action' => 'index', 'middleware' => [ThrottleRequest::class => [10, 60]]]);
        $this->app->router->addGet('/login', ['namespace' => Controllers::class, 'controller' => 'Stub', 'action' => 'index', 'middleware' => [ThrottleLogin::class => [2, 30]]]);
    }

    public function testControllerMiddleware(): void
    {
        $this->assertThrottled('/', 10, 60);
    }

    public function testRouteMiddleware(): void
    {
        $this->assertThrottled('/route', 10, 60);
    }

    public function testLoginMiddleware(): void
    {
        $this->assertThrottled('/login', 2, 30);
    }

    public function testOtherActionsAreNotThrottled(): void
    {
        for ($i = 0; $i < 11; $i++) {
            $this->request('/');
        }

        $response = $this->request('/not-throttled');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->getHeaders()->get('X-RateLimit-Limit'));
    }

    public function testRoutesAreCountedSeparately(): void
    {
        for ($i = 0; $i < 11; $i++) {
            $this->request('/route');
        }

        $this->assertSame('9', $this->request('/')->getHeaders()->get('X-RateLimit-Remaining'));
    }

    public function testCountersInADedicatedStore(): void
    {
        $this->getDI()->getShared(Services::CONFIG)->merge(['security' => ['throttle' => ['store' => 'file']]]);

        $this->request('/route');

        $this->assertNotSame([], glob(self::$cache_dir . '*') ?: []);
        $this->assertNull($this->getDI()->getShared(Services::CACHE . '.memory')->getAdapter()->getKeys() ?: null);
    }

    public function testMiddlewareWithoutName(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(StubThrottleWithoutName::class . '->name is empty.');

        new StubThrottleWithoutName(StubController::class, 1);
    }

    private function assertThrottled(string $url, int $max, int $decay): void
    {
        for ($i = 1; $i <= $max; $i++) {
            $headers = $this->request($url)->getHeaders();

            $this->assertSame((string) $max, $headers->get('X-RateLimit-Limit'), "request $i");
            $this->assertSame((string) ($max - $i), $headers->get('X-RateLimit-Remaining'), "request $i");
            $this->assertFalse($headers->get('Retry-After'), "request $i");
        }

        $response = $this->request($url);
        $headers = $response->getHeaders();

        $this->assertSame(StatusCode::TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertSame(StatusCode::message(StatusCode::TOO_MANY_REQUESTS), $response->getContent());
        $this->assertSame((string) $max, $headers->get('X-RateLimit-Limit'));
        $this->assertSame('0', $headers->get('X-RateLimit-Remaining'));
        $this->assertContains($headers->get('Retry-After'), [(string) $decay, (string) ($decay - 1)]);
    }

    private function request(string $url): Response
    {
        $di = $this->getDI();
        $di->remove(Services::RESPONSE);
        $di->setShared(Services::RESPONSE, new Response());

        $this->dispatch($url);

        /** @var Response */
        return $di->getShared(Services::RESPONSE);
    }
}

final class StubThrottleWithoutName extends Throttle {}

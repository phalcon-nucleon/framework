<?php

declare(strict_types=1);

namespace Test\Http;

use Fake\Core\Listeners\StubListener;
use Fake\Kernels\Http\Controllers\StubController;
use Fake\Kernels\Http\Middlewares\StubRouteMiddleware;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Http\Kernel;
use Neutrino\Foundation\Http\RouteCompiler;
use Neutrino\Foundation\Middleware\Dispatcher as DispatcherMiddleware;
use Neutrino\Http\Middleware\Ajax;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Neutrino\Providers;
use Phalcon\Di\FactoryDefault;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher\Exception as DispatcherException;
use Phalcon\Mvc\Router;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase\TestCase;

final class HttpKernelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        StubController::$middlewares = [];
        StubController::$registerMiddlewares = [];
        StubRouteMiddleware::$calls = [];
    }

    public function testFullRequest(): void
    {
        $this->dispatch('/return');

        $this->assertController('Stub');
        $this->assertAction('return');
        $this->assertSame(StubController::class . '::returnAction', $this->getContent());
    }

    public function testUnknownRoute(): void
    {
        // No notFound() route: the dispatcher looks for the default controller.
        $this->expectException(DispatcherException::class);

        $this->dispatch('/nowhere/at/all');
    }

    public function testRunHandlesTheRequestUriAndSendsTheResponse(): void
    {
        $output = $this->runRequest('/return?query=1');

        $this->assertSame(StubController::class . '::returnAction', $output);
    }

    public function testRunDoesNotSendAResponseTwice(): void
    {
        $this->addRoute('/sent', 'sent');

        $this->assertSame('sent', $this->runRequest('/sent'));
    }

    public function testImplicitViewIsDisabledByDefault(): void
    {
        $this->assertFalse((new \ReflectionProperty(\Phalcon\Mvc\Application::class, 'implicitView'))->getValue($this->app));
    }

    public function testListenerLifeCycle(): void
    {
        $this->dispatch('/');

        $this->assertSame([
            'onBoot',
            'beforeHandleRequest',
            'beforeDispatchLoop',
            'beforeDispatch',
            'beforeExecuteRoute',
            'afterInitialize',
            'afterExecuteRoute',
            'afterDispatch',
            'afterDispatchLoop',
        ], array_keys(StubListener::$instance->views));

        foreach (StubListener::$instance->views as $calls) {
            $this->assertCount(1, $calls);
        }
    }

    public function testMiddlewareReturningFalseStopsTheDispatch(): void
    {
        $this->app->attach(new StoppingMiddleware());

        $this->dispatch('/return');

        $this->assertSame('', $this->getContent());
    }

    /**
     * @return iterable<string, array{mixed, list<array{class-string, list<mixed>}>}>
     */
    public static function routeMiddlewares(): iterable
    {
        yield 'class' => [StubRouteMiddleware::class, [[StubRouteMiddleware::class, []]]];
        yield 'list' => [[StubRouteMiddleware::class, StubRouteMiddleware::class], [[StubRouteMiddleware::class, []], [StubRouteMiddleware::class, []]]];
        yield 'with parameters' => [[StubRouteMiddleware::class => [10, 'b']], [[StubRouteMiddleware::class, [10, 'b']]]];
        yield 'with one parameter' => [[StubRouteMiddleware::class => 10], [[StubRouteMiddleware::class, [10]]]];
    }

    /**
     * @param list<array{class-string, list<mixed>}> $expected
     */
    #[DataProvider('routeMiddlewares')]
    public function testRouteMiddlewares(mixed $middleware, array $expected): void
    {
        $this->addRoute('/with-middleware', 'return', $middleware);

        $this->dispatch('/with-middleware');

        $this->assertSame($expected, StubRouteMiddleware::$calls);
    }

    public function testRouteMiddlewareIsLimitedToTheRouteAction(): void
    {
        $this->addRoute('/forwarded-with-middleware', 'forwarded', StubRouteMiddleware::class);

        $this->dispatch('/forwarded-with-middleware');

        // Applied to the forwarded action only: the forward target (index) is not affected.
        $this->assertSame([[StubRouteMiddleware::class, []]], StubRouteMiddleware::$calls);
    }

    public function testAjaxMiddleware(): void
    {
        $this->addRoute('/ajax', 'return', Ajax::class);

        $this->dispatch('/ajax');
        $this->assertResponseCode(400);
        $this->assertSame('', $this->getContent());

        foreach (['XMLHttpRequest', 'xmlhttprequest', 'XMLHTTPREQUEST'] as $value) {
            $this->dispatch('/ajax', 'GET', [], ['X-Requested-With' => $value]);
            $this->assertSame(StubController::class . '::returnAction', $this->getContent(), $value);
        }
    }

    public function testCompiledRoutesAreLoaded(): void
    {
        $file = BASE_PATH . RouteCompiler::COMPILED_FILE;
        $router = new Router(false);
        $router->addGet('/compiled', ['namespace' => \Fake\Kernels\Http\Controllers::class, 'controller' => 'Stub', 'action' => 'return']);
        RouteCompiler::write($router, BASE_PATH);

        try {
            $kernel = $this->bootstrap->make(RoutesFileKernel::class);
            $kernel->boot();

            /** @var Router $loaded */
            $loaded = $kernel->getDI()->getShared(Services::ROUTER);
            $this->assertSame(['/compiled'], array_map(static fn($route) => $route->getPattern(), $loaded->getRoutes()));
        } finally {
            unlink($file);
        }
    }

    public function testRoutesFileIsLoadedWithoutCompiledRoutes(): void
    {
        $kernel = $this->bootstrap->make(RoutesFileKernel::class);

        /** @var Router $router */
        $router = $kernel->getDI()->getShared(Services::ROUTER);
        $this->assertContains('/get-head', array_map(static fn($route) => $route->getPattern(), $router->getRoutes()));
    }

    private function addRoute(string $pattern, string $action, mixed $middleware = null): void
    {
        /** @var Router $router */
        $router = $this->getDI()->getShared(Services::ROUTER);

        $paths = ['namespace' => \Fake\Kernels\Http\Controllers::class, 'controller' => 'Stub', 'action' => $action];
        if ($middleware !== null) {
            $paths['middleware'] = $middleware;
        }

        $router->addGet($pattern, $paths);
    }

    private function runRequest(string $uri): string
    {
        $server = $_SERVER;
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();
        try {
            $this->bootstrap->run($this->app);

            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
            $_SERVER = $server;
        }
    }
}

class StoppingMiddleware extends DispatcherMiddleware implements BeforeInterface
{
    public function before(Event $event, object $source, mixed $data = null): bool
    {
        return false;
    }
}

class RoutesFileKernel extends Kernel
{
    protected array $providers = [
        Providers\Http\Router::class,
        Providers\Http\Dispatcher::class,
    ];

    protected ?string $dependencyInjection = FactoryDefault::class;
}

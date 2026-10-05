<?php

declare(strict_types=1);

namespace Test\Http;

use Neutrino\Foundation\Http\Exception\UncacheableRouteException;
use Neutrino\Foundation\Http\RouteCompiler;
use Neutrino\Support\Facades\Facade;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Router\Group;
use Phalcon\Mvc\Router\RouteInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

final class RouteCompilerTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/nucleon-routes-' . bin2hex(random_bytes(6));
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        RouteCompiler::clear($this->basePath);
        @rmdir($this->basePath . '/bootstrap/compile');
        @rmdir($this->basePath . '/bootstrap');
        @rmdir($this->basePath);
        Facade::clearResolvedInstances();
        FactoryDefault::reset();
        unset($_SERVER['REQUEST_METHOD']);
    }

    public function testRoundTripOfTheFakeAppRoutes(): void
    {
        $source = $this->router(static function (): void {
            require BASE_PATH . '/routes/http.php';
        });

        $this->assertRoundTrip($source);
    }

    public function testRoundTripOfRouterSettingsNamesHostnamesAndGroups(): void
    {
        $source = $this->router(static function (Router $router): void {
            $router->setDefaults(['namespace' => 'App\\Controllers', 'controller' => 'home', 'action' => 'index', 'params' => ['p' => '1']]);
            $router->removeExtraSlashes(true);
            $router->notFound(['controller' => 'errors', 'action' => 'show404']);
            $router->addGet('/u/{id:[0-9]+}', ['controller' => 'user', 'action' => 'show'])->setName("it's named");
            $router->add('/get-head', ['controller' => 'stub', 'middleware' => ['A' => [1, 'b']]], ['GET', 'HEAD'])->setHostname('api.example.com');
            $router->addPost('/:controller/:action/:params', ['controller' => 1, 'action' => 2, 'params' => 3]);

            $group = new Group(['controller' => 'admin']);
            $group->setPrefix('/admin');
            $group->setHostname('admin.example.com');
            $group->addGet('/users', ['action' => 'users']);
            $router->mount($group);
        });

        $loaded = $this->assertRoundTrip($source);

        $this->assertTrue($this->property($loaded, 'removeExtraSlashes'));
        $this->assertSame(['controller' => 'errors', 'action' => 'show404'], $this->property($loaded, 'notFoundPaths'));

        $loaded->handle('/u/12/');
        $this->assertTrue($loaded->wasMatched());
        $this->assertSame('user', $loaded->getControllerName());
        $this->assertSame('12', $loaded->getParams()['id']);
        $named = $loaded->getRouteByName("it's named");
        $this->assertInstanceOf(RouteInterface::class, $named);
        $this->assertSame('/u/{id:[0-9]+}', $named->getPattern());
    }

    /**
     * @return iterable<string, array{\Closure(Router): void, string}>
     */
    public static function uncacheableRoutes(): iterable
    {
        yield 'converter' => [static function (Router $router): void {
            $router->addGet('/a/{id}', ['controller' => 'a'])->convert('id', 'intval');
        }, 'Route "/a/{id}" cannot be cached: it has converters.'];
        yield 'beforeMatch' => [static function (Router $router): void {
            $router->addGet('/b', ['controller' => 'b'])->beforeMatch(static fn(): bool => true);
        }, 'Route "/b" cannot be cached: it has a beforeMatch callback.'];
        yield 'group beforeMatch' => [static function (Router $router): void {
            $group = new Group(['controller' => 'g']);
            $group->beforeMatch(static fn(): bool => true);
            $group->addGet('/g', ['action' => 'index']);
            $router->mount($group);
        }, 'Route "/g" cannot be cached: it has a beforeMatch callback.']; // Phalcon copies it on the route
        yield 'object in paths' => [static function (Router $router): void {
            $router->addGet('/o', ['controller' => 'o', 'handler' => new stdClass()]);
        }, 'Route "/o" cannot be cached: "handler" is a stdClass.'];
    }

    /**
     * @param \Closure(Router): void $routes
     */
    #[DataProvider('uncacheableRoutes')]
    public function testUncacheableRoutes(\Closure $routes, string $message): void
    {
        $router = $this->router($routes);

        $this->expectException(UncacheableRouteException::class);
        $this->expectExceptionMessage($message);

        RouteCompiler::write($router, $this->basePath);
    }

    public function testFailedCompilationWritesNothing(): void
    {
        $router = $this->router(static function (Router $router): void {
            $router->addGet('/ok', ['controller' => 'ok']);
            $router->addGet('/a/{id}', ['controller' => 'a'])->convert('id', 'intval');
        });

        try {
            RouteCompiler::write($router, $this->basePath);
        } catch (UncacheableRouteException) {
        }

        $this->assertFileDoesNotExist($this->basePath . RouteCompiler::COMPILED_FILE);
    }

    /**
     * @param \Closure(Router): void $routes
     */
    private function router(\Closure $routes): Router
    {
        $di = new FactoryDefault();
        FactoryDefault::setDefault($di);
        $router = new Router(false);
        $di->setShared('router', $router);
        Facade::clearResolvedInstances();
        Facade::setDependencyInjection($di);

        $routes($router);

        return $router;
    }

    private function assertRoundTrip(Router $source): Router
    {
        $file = RouteCompiler::write($source, $this->basePath);
        $this->assertSame($this->basePath . RouteCompiler::COMPILED_FILE, $file);

        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file), $output, $code);
        $this->assertSame(0, $code, implode("\n", $output));

        $loaded = $this->router(static function () use ($file): void {
            require $file;
        });

        $this->assertSame(array_map($this->routeToArray(...), $source->getRoutes()), array_map($this->routeToArray(...), $loaded->getRoutes()));
        $this->assertSame($source->getDefaults(), $loaded->getDefaults());

        return $loaded;
    }

    /**
     * @return array<string, mixed>
     */
    private function routeToArray(RouteInterface $route): array
    {
        return [
            'HttpMethods'     => $route->getHttpMethods(),
            'Hostname'        => $route->getHostname() ?? ($route instanceof Router\Route ? $route->getGroup()?->getHostname() : null),
            'Name'            => $route->getName(),
            'Pattern'         => $route->getPattern(),
            'CompiledPattern' => $route->getCompiledPattern(),
            'Paths'           => $route->getPaths(),
        ];
    }

    private function property(Router $router, string $name): mixed
    {
        return (new \ReflectionProperty(Router::class, $name))->getValue($router);
    }
}

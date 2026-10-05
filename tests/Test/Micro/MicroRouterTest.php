<?php

declare(strict_types=1);

namespace Test\Micro;

use Neutrino\Constants\Events;
use Neutrino\Constants\Services;
use Neutrino\Interfaces\Middleware\AfterInterface;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Neutrino\Micro\Router;
use Neutrino\Support\IdeHelper\Generator;
use Phalcon\Events\Event;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Micro\Collection;
use Phalcon\Mvc\Router\RouteInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase\TestCase;
use UnexpectedValueException;

final class MicroRouterTest extends TestCase
{
    use MicroTestCase;

    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        StubMicroMiddleware::$calls = [];
        StubMicroMiddleware::$return = true;

        $router = $this->getDI()->getShared(Services::MICRO_ROUTER);
        $this->assertInstanceOf(Router::class, $router);
        $this->router = $router;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function httpMethods(): iterable
    {
        foreach (['get', 'post', 'put', 'patch', 'delete', 'options', 'head'] as $method) {
            yield $method => [$method];
        }
    }

    #[DataProvider('httpMethods')]
    public function testClosureHandlerForEachMethod(string $method): void
    {
        $route = $this->router->{'add' . ucfirst($method)}("/test.$method", function () use ($method): ResponseInterface {
            /** @var \Neutrino\Foundation\Micro\Kernel $this */
            return $this->response->setContent("test.$method");
        });

        $this->assertInstanceOf(RouteInterface::class, $route);

        $this->dispatch("/test.$method", strtoupper($method));

        $this->assertSame("test.$method", $this->getContent());
    }

    public function testAddWithMethods(): void
    {
        // Micro binds closure handlers to the application: the response is captured instead of using $this.
        $response = $this->response();
        $this->router->add('/any', fn() => $response->setContent('any'));
        $this->router->add('/some', fn() => $response->setContent('some'), ['GET', 'POST']);

        $this->dispatch('/any', 'PUT');
        $this->assertSame('any', $this->getContent());

        $this->dispatch('/some', 'POST');
        $this->assertSame('some', $this->getContent());

        $this->expectException(\Phalcon\Mvc\Micro\Exception::class);
        $this->dispatch('/some', 'PUT');
    }

    /**
     * @return iterable<string, array{string|array<int|string, mixed>}>
     */
    public static function controllerHandlers(): iterable
    {
        yield 'Controller::action' => [StubMicroController::class . '::index'];
        yield '[Controller, action]' => [[StubMicroController::class, 'index']];
        yield 'paths' => [['controller' => StubMicroController::class, 'action' => 'index']];
    }

    /**
     * @param string|array<int|string, mixed> $handler
     */
    #[DataProvider('controllerHandlers')]
    public function testControllerHandlers(string|array $handler): void
    {
        $this->router->addGet('/micro/{id}', $handler);

        $this->dispatch('/micro/12');

        $this->assertSame(json_encode(['id' => '12']), $this->getContent());
        $this->assertTrue($this->router->wasMatched());
        $this->assertSame('/micro/{id}', $this->router->getMatchedRoute()?->getPattern());
    }

    public function testControllerMiddlewares(): void
    {
        $this->router->addGet('/micro/{id}', [
            'controller'  => StubMicroController::class,
            'action'      => 'index',
            'middlewares' => [StubMicroMiddleware::class, StubMicroMiddleware::class => ['with', 'params']],
        ]);

        $this->dispatch('/micro/1');

        $this->assertSame(json_encode(['id' => '1']), $this->getContent());
        $this->assertEquals([
            ['before', [], new Event(Events\Micro::BEFORE_EXECUTE_ROUTE, $this->app), $this->app],
            ['before', ['with', 'params'], new Event(Events\Micro::BEFORE_EXECUTE_ROUTE, $this->app), $this->app],
            ['after', [], new Event(Events\Micro::AFTER_EXECUTE_ROUTE, $this->app), $this->app],
            ['after', ['with', 'params'], new Event(Events\Micro::AFTER_EXECUTE_ROUTE, $this->app), $this->app],
        ], StubMicroMiddleware::$calls);
    }

    /**
     * @return iterable<string, array{mixed, list<mixed>}>
     */
    public static function singleParameters(): iterable
    {
        yield 'string' => ['api', ['api']];
        yield 'int' => [10, [10]];
    }

    /**
     * @param list<mixed> $expected
     */
    #[DataProvider('singleParameters')]
    public function testControllerMiddlewareWithASingleParameter(mixed $parameter, array $expected): void
    {
        $this->router->addGet('/micro/{id}', [
            'controller'  => StubMicroController::class,
            'action'      => 'index',
            'middlewares' => [StubMicroMiddleware::class => $parameter],
        ]);

        $this->dispatch('/micro/1');

        $this->assertSame([$expected, $expected], array_column(StubMicroMiddleware::$calls, 1));
    }

    public function testControllerMiddlewareReturningFalseStopsTheAction(): void
    {
        StubMicroMiddleware::$return = false;

        $this->router->addGet('/micro/{id}', [
            'controller'  => StubMicroController::class,
            'action'      => 'index',
            'middlewares' => [StubMicroMiddleware::class],
        ]);

        $this->dispatch('/micro/1');

        $this->assertSame('', $this->getContent());
        $this->assertSame(['before'], array_column(StubMicroMiddleware::$calls, 0));
    }

    public function testUnknownAction(): void
    {
        $this->router->addGet('/micro', StubMicroController::class . '::unknown');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Method "unknown" does not exist on "' . StubMicroController::class . '".');

        $this->dispatch('/micro');
    }

    /**
     * @return iterable<string, array{string|array<int|string, mixed>}>
     */
    public static function invalidHandlers(): iterable
    {
        yield 'no action' => [StubMicroController::class];
        yield 'empty' => [[]];
        yield 'no controller' => [['action' => 'index']];
        yield 'middlewares' => [['controller' => StubMicroController::class, 'action' => 'index', 'middlewares' => 'x']];
    }

    /**
     * @param string|array<int|string, mixed> $handler
     */
    #[DataProvider('invalidHandlers')]
    public function testInvalidHandlers(string|array $handler): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->router->addGet('/invalid', $handler);
    }

    public function testInvalidMiddleware(): void
    {
        $this->router->addGet('/micro', ['controller' => StubMicroController::class, 'action' => 'index', 'middlewares' => [\stdClass::class]]);

        $this->expectException(UnexpectedValueException::class);

        $this->dispatch('/micro');
    }

    public function testNotFoundAndMount(): void
    {
        $collection = new Collection();
        $collection->setHandler(StubMicroController::class, true);
        $collection->setPrefix('/collection');
        $collection->get('/{id}', 'index');

        $this->assertSame($this->router, $this->router->mount($collection));
        $response = $this->response();
        $this->assertSame($this->router, $this->router->notFound(fn() => $response->setContent('not found')));

        $this->dispatch('/collection/5');
        $this->assertSame(json_encode(['id' => '5']), $this->getContent());

        $this->dispatch('/nowhere');
        $this->assertSame('not found', $this->getContent());
    }

    public function testRouteAccessors(): void
    {
        // Phalcon 5 passes the route parameters as named arguments.
        $response = $this->response();
        $this->router->addGet('/named/{id}', fn(string $id) => $response->setContent("named $id"))->setName('named');

        $this->dispatch('/named/3');

        $this->assertSame('/named/{id}', $this->router->getRouteByName('named')?->getPattern());
        $this->assertNull($this->router->getRouteByName('unknown'));
        $this->assertContains('/named/{id}', array_map(static fn(RouteInterface $r): string => $r->getPattern(), $this->router->getRoutes()));
        $this->assertSame(['id' => '3'], $this->router->getParams());
        $this->assertSame('', $this->router->getControllerName());
        $this->assertSame('', $this->router->getActionName());
    }

    public function testIdeHelpersKnowTheMicroRouter(): void
    {
        $this->assertSame(Router::class, (new Generator($this->getDI()))->services()[Services::MICRO_ROUTER]);
    }

    private function response(): ResponseInterface
    {
        /** @var ResponseInterface */
        return $this->getDI()->getShared(Services::RESPONSE);
    }
}

class StubMicroController extends Controller
{
    public function index(string $id = ''): ResponseInterface
    {
        return $this->response->setJsonContent(['id' => $id]);
    }
}

class StubMicroMiddleware extends \Neutrino\Foundation\Middleware\Controller implements BeforeInterface, AfterInterface
{
    /** @var list<array{string, list<mixed>, Event, object}> */
    public static array $calls = [];

    public static bool $return = true;

    /** @var list<mixed> */
    private array $params;

    public function __construct(string $controllerClass, mixed ...$params)
    {
        parent::__construct($controllerClass);

        $this->params = array_values($params);
    }

    public function before(Event $event, object $source, mixed $data = null): bool
    {
        self::$calls[] = ['before', $this->params, $event, $source];

        return self::$return;
    }

    public function after(Event $event, object $source, mixed $data = null): void
    {
        self::$calls[] = ['after', $this->params, $event, $source];
    }
}

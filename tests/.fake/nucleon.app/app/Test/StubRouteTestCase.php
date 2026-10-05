<?php

declare(strict_types=1);

namespace Fake\Test;

use Fake\Kernels\Http\Controllers\StubController;
use Neutrino\Test\RoutesTestCase;
use Test\TestCase\TraitTestCase;

/**
 * Routes test case of the fake app. Its assertRoute() records the calls instead of asserting.
 */
class StubRouteTestCase extends RoutesTestCase
{
    use TraitTestCase;

    /** @var list<array{string, string, bool, ?string, ?string, ?array<string, mixed>}> */
    public array $assertedRoutes = [];

    protected static function routes(): array
    {
        return [
            static::formatDataRoute('/', 'GET', true),
            static::formatDataRoute('/', 'POST', false),
            static::formatDataRoute('/something/:int', 'GET', true, 'index', StubController::class, ['id' => 1]),
        ];
    }

    public function assertRoute(
        string $route,
        string $method,
        bool $expected,
        ?string $controller = null,
        ?string $action = null,
        ?array $params = null,
    ): void {
        $this->assertedRoutes[] = [$route, $method, $expected, $controller, $action, $params];
    }
}

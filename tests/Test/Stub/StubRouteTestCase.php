<?php

namespace Test\Stub;

use Neutrino\Test\RoutesTestCase;
use Test\TestCase\TraitTestCase;

class StubRouteTestCase extends RoutesTestCase
{
    use TraitTestCase;

    /**
     * @return array[]
     */
    protected function routes()
    {
        return [
            $this->formatDataRoute('/', 'GET', true),
            $this->formatDataRoute('/', 'POST', false),
            $this->formatDataRoute('/something/:int', 'GET', true, 'index', StubController::class, [1])
        ];
    }

    public function assertRoute(
        $route,
        $method,
        $expected,
        $controller = null,
        $action = null,
        array $params = null
    )
    {
        static::$testedRoutes[] = [$route, $method, $expected, $controller, $action, $params];
    }
}
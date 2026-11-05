<?php

declare(strict_types=1);

namespace Test\Assert;

use Neutrino\Foundation\Http\Kernel;
use Neutrino\Providers;
use Neutrino\Test\RoutesTestCase;
use Phalcon\Di\FactoryDefault;

/**
 * RoutesTestCase run for real on the fake app's routes/http.php: every route is tested,
 * so testRoutesTested() must pass for each of them (none incomplete).
 */
final class AppRoutesTest extends RoutesTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::setConfig(['app' => ['base_uri' => '/']]);
    }

    protected static function kernelClassInstance(): string
    {
        return RoutesKernel::class;
    }

    protected static function routes(): array
    {
        return [
            static::formatDataRoute('/get', 'GET', true, 'Stub', 'index'),
            static::formatDataRoute('/get', 'POST', false),
            static::formatDataRoute('/post', 'POST', true, 'Stub', 'index'),
            static::formatDataRoute('/u/12', 'GET', true, 'Stub', 'index', ['user' => '12']),
            static::formatDataRoute('/u/abc', 'GET', false),
            static::formatDataRoute('/get-head', 'HEAD', true, 'Stub', 'index'),
            static::formatDataRoute('/get-head', 'POST', false),
            // The paths do not map :controller and :action (no 'controller' => 1): only the match is checked.
            static::formatDataRoute('/back/stub/data', 'GET', true),
        ];
    }
}

final class RoutesKernel extends Kernel
{
    protected array $providers = [
        Providers\Http\Router::class,
        Providers\Http\Dispatcher::class,
    ];

    protected ?string $dependencyInjection = FactoryDefault::class;
}

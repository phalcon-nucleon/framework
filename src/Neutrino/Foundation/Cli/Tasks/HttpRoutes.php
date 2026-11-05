<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Constants\Services;
use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\Providers\Http\Dispatcher as DispatcherProvider;
use Neutrino\Providers\Http\Router as RouterProvider;
use Neutrino\Support\Facades\Facade;
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Router;

/**
 * The HTTP router and dispatcher of the application, loaded from `routes/http.php` in a separate container,
 * so that the console services are left untouched.
 *
 * @internal
 */
final class HttpRoutes
{
    private function __construct(public readonly Router $router, public readonly Dispatcher $dispatcher) {}

    public static function load(DiInterface $cli): self
    {
        $di = new FactoryDefault();
        $di->setShared(Services::CONFIG, $cli->getShared(Services::CONFIG));
        ProviderRegistrar::register($di, [RouterProvider::class, DispatcherProvider::class]);

        Facade::clearResolvedInstances();
        Facade::setDependencyInjection($di);
        Di::setDefault($di);

        try {
            (static function (): void {
                require BASE_PATH . '/routes/http.php';
            })();

            /** @var Router $router */
            $router = $di->getShared(Services::ROUTER);
            /** @var Dispatcher $dispatcher */
            $dispatcher = $di->getShared(Services::DISPATCHER);

            return new self($router, $dispatcher);
        } finally {
            Facade::clearResolvedInstances();
            Facade::setDependencyInjection($cli);
            Di::setDefault($cli);
        }
    }
}

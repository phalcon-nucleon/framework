<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Attribute\Option;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Output\Helper;
use Neutrino\Cli\Output\Table;
use Neutrino\Cli\Task;
use Neutrino\Support\Str;
use Phalcon\Mvc\Router\RouteInterface;

final class RouteListTask extends Task
{
    #[Description('List the HTTP routes.')]
    #[Option('--no-substitution', 'Show the raw patterns, without replacing the placeholders.')]
    public function mainAction(): void
    {
        $http = HttpRoutes::load($this->getDI());
        $defaults = $http->router->getDefaults();
        $controllerSuffix = $http->dispatcher->getHandlerSuffix();
        $actionSuffix = $http->dispatcher->getActionSuffix();

        $datas = [];

        /** @var RouteInterface $route */
        foreach ($http->router->getRoutes() as $route) {
            $paths = $route->getPaths();

            $httpMethods = $route->getHttpMethods();
            if (is_array($httpMethods)) {
                $httpMethods = implode('|', $httpMethods);
            }

            $controller = isset($paths['controller']) && is_string($paths['controller'])
                ? Str::capitalize($paths['controller'])
                : Decorate::notice('{controller}');
            $action = isset($paths['action']) && is_string($paths['action']) ? $paths['action'] : Decorate::notice('{action}');

            $module = $paths['module'] ?? '';
            $namespace = $paths['namespace'] ?? $defaults['namespace'] ?? '';
            $module = is_string($module) ? $module : '';
            $namespace = is_string($namespace) ? $namespace : '';

            $datas[$module . '::' . $namespace][] = [
                'domain'     => (string) $route->getHostname(),
                'name'       => (string) $route->getName(),
                'method'     => (string) $httpMethods,
                'pattern'    => $this->hasOption('no-substitution') ? $route->getPattern() : Helper::describeRoutePattern($route, true),
                'action'     => $controller . $controllerSuffix . '::' . $action . $actionSuffix,
                'middleware' => self::middlewares($paths['middleware'] ?? null),
            ];
        }

        foreach ($datas as $key => $data) {
            [$module, $namespace] = explode('::', $key, 2);

            $this->table([['MODULE    : ' . $module], ['NAMESPACE : ' . $namespace]], [], Table::NO_HEADER);
            $this->table($data);
            $this->line('');
        }
    }

    private static function middlewares(mixed $middlewares): string
    {
        if (!is_array($middlewares)) {
            return is_string($middlewares) ? $middlewares : '';
        }

        $names = [];
        foreach ($middlewares as $key => $middleware) {
            $names[] = is_int($key) ? (is_string($middleware) ? $middleware : '') : $key;
        }

        return implode('|', $names);
    }
}

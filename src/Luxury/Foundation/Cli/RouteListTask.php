<?php

namespace Luxury\Foundation\Cli;

use Luxury\Cli\Task;
use Luxury\Foundation\Application;
use Luxury\Support\Arr;
use Luxury\Support\Str;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Router\Route;

/**
 * Class RouteListTask
 *
 * @package     Luxury\Foundation\Cli
 */
class RouteListTask extends Task
{
    /**
     * List all routes.
     */
    public function mainAction()
    {
        $luxury = new Application($this->config);

        $httpApp = $luxury->make('App\\Http\\Kernel');

        $routes = $httpApp->router->getRoutes();

        $datas = [];
        foreach ($routes as $route) {
            /** @var Route $route */
            $paths = $route->getPaths();

            $reverses = $route->getReversedPaths();

            if (!$this->hasOption('no-compile')) {
                $compiled = $route->getCompiledPattern();
                if ($compiled !== $route->getPattern()) {
                    foreach ($reverses as $key => $reverse) {
                        if (is_int($key)) {
                            $compiled = preg_replace('/\([^\/\)]+\)/', '{' . $reverse . '}', $compiled, 1);
                        }
                    }
                    $compiled = substr($compiled, 2, strlen($compiled) - 5);
                }
            } else {
                $compiled = $route->getPattern();
            }

            $httpMethods = $route->getHttpMethods();

            if (is_array($httpMethods)) {
                $httpMethods = implode('|', $httpMethods);
            }

            $datas[] = [
                'domain'  => $route->getHostname(),
                'name'    => $route->getName(),
                'method'  => $httpMethods,
                'pattern' => $compiled,
                'action'  => Arr::fetch($paths, 'namespace', 'App\\Http\\Controllers') .
                    '\\' . Str::capitalize($paths['controller']) . 'Controller::' . $paths['action']
            ];
        }

        $this->table($datas);
    }
}

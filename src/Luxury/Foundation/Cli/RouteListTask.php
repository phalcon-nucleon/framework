<?php

namespace Luxury\Foundation\Cli;

use Luxury\Cli\Output\Helper;
use Luxury\Cli\Task;
use Luxury\Constants\Services;
use Luxury\Support\Arr;
use Luxury\Support\Facades\Router;
use Luxury\Support\Str;

/**
 * Class RouteListTask
 *
 * @package     Luxury\Foundation\Cli
 */
class RouteListTask extends Task
{
    /**
     * List all routes.
     *
     * @description List all routes.
     *
     * @option      --no-substitution: Doesn't replace matching group by params name
     */
    public function mainAction()
    {
        $routes = $this->getHttpRoutes();

        $datas = [];
        foreach ($routes as $route) {
            /** @var \Phalcon\Mvc\Router\Route $route */
            $paths = $route->getPaths();

            if (!$this->hasOption('no-substitution')) {
                $compiled = Helper::describeRoutePattern($route);
            } else {
                $compiled = $route->getPattern();
            }

            $httpMethods = $route->getHttpMethods();

            if (is_array($httpMethods)) {
                $httpMethods = implode('|', $httpMethods);
            }
            $middleware = Arr::fetch($paths, 'middleware');
            if (is_array($middleware)) {
                $middleware = implode('|', $middleware);
            }
            $datas[] = [
                'domain'     => $route->getHostname(),
                'name'       => $route->getName(),
                'method'     => $httpMethods,
                'pattern'    => $compiled,
                'action'     => Arr::fetch($paths, 'namespace', 'App\\Http\\Controllers') .
                    '\\' . Str::capitalize($paths['controller']) . 'Controller::' . $paths['action'],
                'middleware' => $middleware
            ];
        }

        $this->table($datas);
    }

    protected function getHttpRoutes()
    {
        Router::clearResolvedInstances();

        $cliRouter = $this->router;

        $this->di->remove(Services::ROUTER);

        $httpRouterProvider = new \Luxury\Providers\Http\Router;

        $httpRouterProvider->registering();

        require $this->config->paths->routes . 'http.php';

        $routes = Router::getRoutes();

        Router::clearResolvedInstances();

        $this->di->remove(Services::ROUTER);

        $this->di->setShared(Services::ROUTER, $cliRouter);

        return $routes;
    }
}

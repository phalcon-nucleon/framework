<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Output\Helper;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Neutrino\Support\Arr;
use Neutrino\Support\Facades\Router;
use Neutrino\Support\Str;

/**
 * Class RouteListTask
 *
 *  @package Neutrino\Foundation\Cli
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
            $middlewares = Arr::fetch($paths, 'middleware');
            if (is_array($middlewares)) {
                $_middlewares = [];
                foreach ($middlewares as $key => $middleware) {
                    if(is_int($key)){
                        $_middlewares[] = $middleware;
                    } else {
                        $_middlewares[] = $key;
                    }
                }
                $middleware = implode('|', $_middlewares);
            } else {
                $middleware = $middlewares;
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

    /**
     * List the Http Routes
     *
     * @return \Phalcon\Mvc\Router\RouteInterface[]
     */
    protected function getHttpRoutes()
    {
        Router::clearResolvedInstances();

        $cliRouter = $this->router;

        $this->di->remove(Services::ROUTER);

        $httpRouterProvider = new \Neutrino\Providers\Http\Router;

        $httpRouterProvider->registering();

        require BASE_PATH .'/routes/http.php';

        $routes = Router::getRoutes();

        Router::clearResolvedInstances();

        $this->di->remove(Services::ROUTER);

        $this->di->setShared(Services::ROUTER, $cliRouter);

        return $routes;
    }
}

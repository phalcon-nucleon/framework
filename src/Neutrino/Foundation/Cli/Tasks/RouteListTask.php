<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Output\Helper;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Neutrino\Support\Arr;
use Neutrino\Support\Facades\Router;
use Neutrino\Support\Str;
use Phalcon\Di\Service;

/**
 * Class RouteListTask
 *
 * @package Neutrino\Foundation\Cli
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
        $infos = $this->getHttpRoutesInfos();

        $datas = [];
        foreach ($infos['routes'] as $route) {
            /** @var \Phalcon\Mvc\Router\Route $route */
            $paths = $route->getPaths();

            if (!$this->hasOption('no-substitution')) {
                $compiled = Helper::describeRoutePattern($route, true);
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
                    if (is_int($key)) {
                        $_middlewares[] = $middleware;
                    } else {
                        $_middlewares[] = $key;
                    }
                }
                $middleware = implode('|', $_middlewares);
            } else {
                $middleware = $middlewares;
            }

            if (Arr::has($paths, 'controller')) {
                $controller = Str::capitalize($paths['controller']);
            } else {
                $controller = Decorate::notice('{controller}');
            }

            $controller .= Arr::fetch($infos, 'controllerSuffix', '');

            if (Arr::has($paths, 'action')) {
                $action = $paths['action'];
            } else {
                $action = Decorate::notice('{action}');
            }

            $action .= Arr::fetch($infos, 'actionSuffix', '');

            $datas[] = [
                'domain'     => $route->getHostname(),
                'name'       => $route->getName(),
                'method'     => $httpMethods,
                'pattern'    => $compiled,
                'action'     => Arr::fetch($paths, 'namespace', Arr::fetch($infos['defaults'], 'namespace')) . '\\' . $controller . '::' . $action,
                'middleware' => $middleware
            ];
        }

        $this->table($datas);
    }

    /**
     * List the Http Routes
     *
     * @return array
     */
    protected function getHttpRoutesInfos()
    {
        Router::clearResolvedInstances();

        $cliRouter = $this->router;
        $cliDispatcher = $this->dispatcher;

        $this->di->remove(Services::ROUTER);
        $this->di->remove(Services::DISPATCHER);

        $httpRouterProvider = new \Neutrino\Providers\Http\Router;
        $httpRouterProvider->registering();
        $httpDispatcherProvider = new \Neutrino\Providers\Http\Dispatcher;
        $httpDispatcherProvider->registering();

        require BASE_PATH . '/routes/http.php';
        /** @var \Phalcon\Mvc\Dispatcher $httpDispatcher */
        $httpDispatcher = $this->di->get(Services::DISPATCHER);
        $reflexionProperty = (new \ReflectionClass(get_class($httpDispatcher)))->getProperty('_handlerSuffix');
        $reflexionProperty->setAccessible(true);

        $routes = Router::getRoutes();
        $defaults = Router::getDefaults();
        $actionSuffix = $httpDispatcher->getActionSuffix();
        $controllerSuffix = $reflexionProperty->getValue($httpDispatcher);

        Router::clearResolvedInstances();

        $this->di->remove(Services::ROUTER);
        $this->di->remove(Services::DISPATCHER);

        $this->di->setShared(Services::ROUTER, $cliRouter);
        $this->di->setShared(Services::DISPATCHER, $cliDispatcher);

        return [
            'routes'           => $routes,
            'defaults'         => $defaults,
            'actionSuffix'     => $actionSuffix,
            'controllerSuffix' => $controllerSuffix,
        ];
    }
}

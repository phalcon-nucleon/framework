<?php

namespace Neutrino\Debug;

use Neutrino\Constants\Events\Kernel;
use Neutrino\Constants\Services;
use Phalcon\Di;

/**
 * Class DebugToolbar
 *
 * @package Neutrino\Debug
 */
class DebugToolbar
{
    public static function register()
    {
        Debugger::getGlobalEventsManager()->attach(Kernel::TERMINATE, function () {
            $mem_peak = memory_get_peak_usage();
            $render_time = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);

            $events = DebugEventsManagerWrapper::getEvents();

            if (!self::toolbarIsAllowed()) {
                return;
            }

            $httpInfo = self::getHttpInfo();
            $buildInfo = Debugger::getBuildInfo();
            $phpErrors = DebugErrorLogger::errors();
            $viewsProfiles = Debugger::getViewProfiles();
            $registeredProfilers = Debugger::getRegisteredProfilers();

            $view = Debugger::getIsolateView();

            $view->setVar('mem_peak', $mem_peak);
            $view->setVar('render_time', $render_time);
            $view->setVar('events', $events);
            $view->setVars($httpInfo);
            $view->setVar('build', $buildInfo);
            $view->setVar('php_errors', $phpErrors);
            $view->setVar('viewProfiles', $viewsProfiles);
            $view->setVar('profilers', $registeredProfilers);

            echo $view->render('bar');
        });
    }

    private static function toolbarIsAllowed()
    {
        $di = Di::getDefault();
        /** @var \Phalcon\Http\Request $request */
        $request = $di->get(Services::REQUEST);

        if ($request->isAjax()) {
            return false;
        }

        /** @var \Phalcon\Http\Response $response */
        $response = $di->get(Services::RESPONSE);

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 300 && $statusCode < 400) {
            return false;
        }

        $contentType = $response->getHeaders()->get('Content-Type');
        if(false !== $contentType && false === strpos($contentType, 'html')){
            return false;
        }

        $contentDisposition = $response->getHeaders()->get('Content-Disposition');
        if(false !== $contentDisposition && false !== strpos($contentDisposition, 'attachment;')){
            return false;
        }

        return true;
    }

    private static function getHttpInfo()
    {
        $di = Di::getDefault();
        /** @var \Phalcon\Mvc\Dispatcher $dispatcher */
        $dispatcher = $di->get('dispatcher');
        /** @var \Phalcon\Http\Response $response */
        $response = $di->get('response');
        /** @var \Phalcon\Http\Request $request */
        $request = $di->get('request');
        /** @var \Phalcon\Mvc\Router $router */
        $router = $di->get('router');

        $module = $dispatcher->getModuleName();
        $controllerClass = $dispatcher->getHandlerClass();
        $controller = $dispatcher->getControllerName();
        $method = $dispatcher->getActionName();
        $route = $router->getMatchedRoute();
        $httpCode = $response->getStatusCode() ?: 200;
        $httpMethodRequest = $request->getMethod();

        return [
            'requestHttpMethod' => $httpMethodRequest,
            'responseHttpCode' => $httpCode,
            'dispatch' => [
                'module' => $module,
                'controllerClass' => $controllerClass,
                'controller' => $controller,
                'method' => $method,
            ],
            'route' => [
                'pattern' => $route ? $route->getPattern() : null,
                'name' => $route ? $route->getName() : null,
                'id' => $route ? $route->getRouteId() : null,
            ],
        ];
    }
}

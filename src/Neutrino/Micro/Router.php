<?php

namespace Neutrino\Micro;

use Phalcon\Di\Injectable;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Micro\Collection;

/**
 * Class Router
 *
 * @property \Phalcon\Mvc\Micro $application
 *
 * @package Neutrino\Micro
 */
class Router extends Injectable implements RouterInterface
{
    /** @var \Phalcon\Mvc\Router */
    private $router;

    /** @var \Phalcon\Mvc\Micro */
    private $application;

    /**
     * Sets the name of the default module
     *
     * @param string $moduleName
     */
    public function setDefaultModule($moduleName)
    {
        throw new \RuntimeException(__METHOD__ . ' doesn\'t support modules');
    }

    /**
     * Sets the default controller name
     *
     * @param string $controllerName
     */
    public function setDefaultController($controllerName)
    {
        $this->router->setDefaultController($controllerName);
    }

    /**
     * Sets the default action name
     *
     * @param string $actionName
     */
    public function setDefaultAction($actionName)
    {
        $this->router->setDefaultAction($actionName);
    }

    /**
     * Sets an array of default paths
     *
     * @param array $defaults
     */
    public function setDefaults(array $defaults)
    {
        $this->router->setDefaults($defaults);
    }

    /**
     * Handles routing information received from the rewrite engine
     *
     * @param string $uri
     */
    public function handle($uri = null)
    {
        $this->router->handle($uri);
    }

    /**
     * Adds a route to the router on any HTTP method
     *
     * @param string $pattern
     * @param mixed  $paths
     * @param mixed  $httpMethods
     *
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function add($pattern, $paths = null, $httpMethods = null)
    {
        foreach ($httpMethods as $httpMethod) {
            $this->application->{strtolower($httpMethod)}($pattern, self::pathToHandler($paths));
        }

        return null;
    }

    /**
     * Adds a route to the router that only match if the HTTP method is GET
     *
     * @param string $pattern
     * @param mixed  $paths
     *
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addGet($pattern, $paths = null)
    {
        return $this->application->get($pattern, self::pathToHandler($paths));
    }

    /**
     * Adds a route to the router that only match if the HTTP method is POST
     *
     * @param string $pattern
     * @param mixed  $paths
     *
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addPost($pattern, $paths = null)
    {
        return $this->application->post($pattern, self::pathToHandler($paths));
    }

    /**
     * Adds a route to the router that only match if the HTTP method is PUT
     *
     * @param string $pattern
     * @param mixed  $paths
     *
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addPut($pattern, $paths = null)
    {
        return $this->application->put($pattern, self::pathToHandler($paths));
    }

    /**
     * Adds a route to the router that only match if the HTTP method is PATCH
     *
     * @param string $pattern
     * @param mixed  $paths
     *
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addPatch($pattern, $paths = null)
    {
        return $this->application->patch($pattern, self::pathToHandler($paths));
    }

    /**
     * Adds a route to the router that only match if the HTTP method is DELETE
     *
     * @param string $pattern
     * @param mixed  $paths
     *
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addDelete($pattern, $paths = null)
    {
        return $this->application->delete($pattern, self::pathToHandler($paths));
    }

    /**
     * Add a route to the router that only match if the HTTP method is OPTIONS
     *
     * @param string $pattern
     * @param mixed  $paths
     *
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addOptions($pattern, $paths = null)
    {
        return $this->application->options($pattern, self::pathToHandler($paths));
    }

    /**
     * Adds a route to the router that only match if the HTTP method is HEAD
     *
     * @param string $pattern
     * @param mixed  $paths
     *
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addHead($pattern, $paths = null)
    {
        return $this->application->head($pattern, self::pathToHandler($paths));
    }

    /**
     * Adds a route to the router that only match if the HTTP method is PURGE (Squid and Varnish support)
     *
     * @param string $pattern
     * @param mixed  $paths
     *
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addPurge($pattern, $paths = null)
    {
        throw new \RuntimeException(__METHOD__ . ': Micro Application doesn\'t support HTTP PURGE method.');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is TRACE
     *
     * @param string $pattern
     * @param mixed  $paths
     *
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addTrace($pattern, $paths = null)
    {
        throw new \RuntimeException(__METHOD__ . ': Micro Application doesn\'t support HTTP TRACE method.');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is CONNECT
     *
     * @param string $pattern
     * @param mixed  $paths
     *
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addConnect($pattern, $paths = null)
    {
        throw new \RuntimeException(__METHOD__ . ': Micro Application doesn\'t support HTTP CONNECT method.');
    }

    /**
     * Mounts a group of routes in the router
     *
     * @param \Phalcon\Mvc\Micro\Collection $collection
     *
     * @return RouterInterface
     */
    public function mount(Collection $collection)
    {
        $this->application->mount($collection);

        return $this;
    }

    /**
     * Removes all the defined routes
     */
    public function clear()
    {
        throw new \RuntimeException(__METHOD__ . ': you can\'t clear the router in micro application.');
    }

    /**
     * Returns processed module name
     *
     * @return string
     */
    public function getModuleName()
    {
        throw new \RuntimeException(__METHOD__ . ' doesn\'t support modules');
    }

    /**
     * Returns processed namespace name
     *
     * @return string
     */
    public function getNamespaceName()
    {
        return $this->router->getNamespaceName();
    }

    /**
     * Returns processed controller name
     *
     * @return string
     */
    public function getControllerName()
    {
        return $this->router->getControllerName();
    }

    /**
     * Returns processed action name
     *
     * @return string
     */
    public function getActionName()
    {
        return $this->router->getActionName();
    }

    /**
     * Returns processed extra params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->router->getParams();
    }

    /**
     * Returns the route that matches the handled URI
     *
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function getMatchedRoute()
    {
        return $this->router->getMatchedRoute();
    }

    /**
     * Return the sub expressions in the regular expression matched
     *
     * @return array
     */
    public function getMatches()
    {
        return $this->router->getMatches();
    }

    /**
     * Check if the router matches any of the defined routes
     *
     * @return bool
     */
    public function wasMatched()
    {
        return $this->router->wasMatched();
    }

    /**
     * Return all the routes defined in the router
     *
     * @return \Phalcon\Mvc\Router\RouteInterface[]
     */
    public function getRoutes()
    {
        return $this->router->getRoutes();
    }

    /**
     * Returns a route object by its id
     *
     * @param mixed $id
     *
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function getRouteById($id)
    {
        return $this->router->getRouteById($id);
    }

    /**
     * Returns a route object by its name
     *
     * @param string $name
     *
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function getRouteByName($name)
    {
        return $this->router->getRouteByName($name);
    }

    protected static function pathToHandler($path){
        if($path instanceof \Closure){
            return $path;
        }

        if(is_array($path)){
            return function ($_ = null) use ($path) {
                /** @var Micro $this */

                $controller = arr_get($path, 'controller');
                $action = arr_get($path, 'action');

                if(!class_exists($controller)){
                    throw new \RuntimeException(/* TODO */);
                }

                $handler = $this->getDI()->get($controller);

                if(!method_exists($handler, $action)){
                    throw new \RuntimeException('Method : "' . $action . '" doesn\'t exist on "' . $controller . '"');
                }

                if(is_null($_)){
                    return $handler->$action(...func_get_args());
                } else {
                    return $handler->$action();
                }
            };
        }

        throw new \RuntimeException("invalid route paths");
    }
}
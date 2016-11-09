<?php

namespace Luxury\Foundation\Kernel;

use Luxury\Constants\Events\Http\Application;
use Luxury\Foundation\Kernelize;
use Luxury\Interfaces\Kernelable;
use Phalcon\Config;
use Phalcon\Di\FactoryDefault as Di;
use Phalcon\Mvc\Application as PhApplication;

/**
 * Class Http
 *
 * @package Luxury\Foundation\Kernel
 */
abstract class Http extends PhApplication implements Kernelable
{
    use Kernelize {
        bootstrap as kernelizeBootstrap;
    }

    /**
     * Return the Provider List to load.
     *
     * @var string[]
     */
    protected $providers = [];

    /**
     * Return the Middlewares to attach onto the application.
     *
     * @var string[]
     */
    protected $middlewares = [];

    /**
     * Return the Events Listeners to attach onto the application.
     *
     * @var string[]
     */
    protected $listeners = [];

    /**
     * The DependencyInjection class to use.
     *
     * @var string
     */
    protected $dependencyInjection = Di::class;

    /**
     * Application constructor.
     */
    public function __construct()
    {
        parent::__construct(null);
    }

    /**
     * Application starter
     *
     * @param \Phalcon\Config $config
     *
     * @return void
     */
    public function bootstrap(Config $config)
    {
        $this->kernelizeBootstrap($config);

        $this->useImplicitView(isset($config->app->useImplicitView) ? $config->app->useImplicitView : false);

        $this->getEventsManager()->attach(Application::BEFORE_HANDLE, function () {
            $this->registerMiddlewareBeforeHandle();
        });
    }

    /**
     * Register the routes of the application.
     */
    public function registerRoutes()
    {
        require $this->config->paths->routes . 'http.php';
    }

    /**
     * Attach middleware specified in the route, if a route was matched
     */
    protected function registerMiddlewareBeforeHandle(){
        $router = $this->router;

        if ($router->wasMatched()) {
            $route = $router->getMatchedRoute();

            $paths = $route->getPaths();

            if (!empty($paths['middleware'])) {
                $middlewares = $paths['middleware'];

                if (!is_array($middlewares)) {
                    $middlewares = [$middlewares];
                }

                foreach ($middlewares as $key => $middleware) {
                    if (is_int($key)) {
                        $middlewareClass = $middleware;
                        $middlewareParams = [];
                    } else {
                        $middlewareClass = $key;
                        $middlewareParams = !is_array($middlewares) ? [$middleware] : $middleware;
                    }

                    $this->attach(new $middlewareClass(...$middlewareParams));
                }
            }
        }
    }
}

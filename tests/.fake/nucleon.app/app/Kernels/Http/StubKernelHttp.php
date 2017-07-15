<?php

namespace Fake\Kernels\Http;

use Fake\Core\Listeners\StubListener;
use Fake\Kernels\Http\Middlewares\StubMiddleware;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Http\Kernel as HttpApplication;
use Neutrino\Providers;
use Phalcon\Mvc\Router;

/**
 * Class TestKernel
 */
class StubKernelHttp extends HttpApplication
{
    /**
     * Return the Provider List to load.
     *
     * @var string[]
     */
    protected $providers = [
        /*
         * Basic Configuration
         */
        //LoggerProvider::class,
        Providers\Url::class,
        Providers\Http\Router::class,
        Providers\Http\Dispatcher::class,
        //FlashProvider::class,
        //SessionProvider::class,
        //Providers\View::class,
        Providers\Cache::class,
        //DatabaseProvider::class,
        /*
         * Service provided by the Phalcon\Di\FactoryDefault
         *
        \Neutrino\Providers\Models::class,
        \Neutrino\Providers\Cookies::class,
        \Neutrino\Providers\Filter::class,
        \Neutrino\Providers\Escaper::class,
        \Neutrino\Providers\Security::class,
        \Neutrino\Providers\Crypt::class,
        \Neutrino\Providers\Annotations::class,
        /**/
    ];

    /**
     * Return the Events Listeners to attach onto the application.
     *
     * @var string[]
     */
    protected $listeners = [
        StubListener::class
    ];

    /**
     * Return the Middleware List to load.
     *
     * @var string[]
     */
    protected $middlewares = [
        StubMiddleware::class
    ];

    /**
     * Register the routes of the application.
     */
    public function registerRoutes()
    {
        /** @var Router $router */
        $router = $this->getDI()->getShared(Services::ROUTER);

        $router->addGet('/', [
            'namespace'  => \Fake\Kernels\Http\Controllers::class,
            'controller' => 'Stub',
            'action'     => 'index'
        ]);
        $router->addPost('/', [
            'namespace'  => \Fake\Kernels\Http\Controllers::class,
            'controller' => 'Stub',
            'action'     => 'index'
        ]);
        $router->addGet('/return', [
            'namespace'  => \Fake\Kernels\Http\Controllers::class,
            'controller' => 'Stub',
            'action'     => 'return'
        ]);
        $router->addGet('/redirect', [
            'namespace'  => \Fake\Kernels\Http\Controllers::class,
            'controller' => 'Stub',
            'action'     => 'redirect'
        ]);
        $router->addGet('/parameted/([\w_-]+)(?:/:int)?', [
            'namespace'  => \Fake\Kernels\Http\Controllers::class,
            'controller' => 'Stub',
            'action'     => 'index',
            'tags'   => 1,
            'page'   => 2,
        ]);
        $router->addGet('/forwarded', [
            'namespace'  => \Fake\Kernels\Http\Controllers::class,
            'controller' => 'Stub',
            'action'     => 'forwarded'
        ]);
    }
}

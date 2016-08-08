<?php

namespace Stub;

use Luxury\Foundation\Application\Http as HttpApplication;
use Luxury\Providers\Http\Dispatcher as DispatcherProvider;

/**
 * Class TestKernel
 */
class StubKernel extends HttpApplication
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
        //UrlProvider::class,
        //FlashProvider::class,
        //SessionProvider::class,
        //RouterProvider::class,
        //ViewProvider::class,
        DispatcherProvider::class,
        //DatabaseProvider::class,
        /*
         * Service provided by the Phalcon\Di\FactoryDefault
         *
        \Luxury\Providers\Models::class,
        \Luxury\Providers\Cookies::class,
        \Luxury\Providers\Filter::class,
        \Luxury\Providers\Escaper::class,
        \Luxury\Providers\Security::class,
        \Luxury\Providers\Crypt::class,
        \Luxury\Providers\Annotations::class,
        /**/
    ];

    /**
     * Return the Middleware List to load.
     *
     * @var string[]
     */
    protected $middlewares = [];

    /**
     * Register the routes of the application.
     */
    public function registerRoutes()
    {
    }
}

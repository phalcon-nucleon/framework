<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Http;

use Neutrino\Constants\Services;
use Neutrino\Error;
use Neutrino\Foundation\Kernelize;
use Neutrino\Interfaces\Kernelable;
use Phalcon\Di\FactoryDefault as Di;
use Phalcon\Events\Manager as EventManager;
use Phalcon\Mvc\Application;

/**
 * Base class of the application's HTTP kernel.
 */
abstract class Kernel extends Application implements Kernelable
{
    use Kernelize {
        boot as private bootKernel;
    }

    /**
     * Providers to register.
     *
     * @var array<int|string, string>
     */
    protected array $providers = [];

    /**
     * Middlewares to attach to the application.
     *
     * @var list<class-string<\Neutrino\Events\Listener>>
     */
    protected array $middlewares = [];

    /**
     * Events listeners to attach to the application.
     *
     * @var list<class-string<\Neutrino\Events\Listener>>
     */
    protected array $listeners = [];

    /**
     * The container class. `null` uses the current default container.
     *
     * @var class-string<\Phalcon\Di\DiInterface>|null
     */
    protected ?string $dependencyInjection = Di::class;

    /**
     * The events manager class. `null` disables the events manager.
     *
     * @var class-string<\Phalcon\Events\ManagerInterface>|null
     */
    protected ?string $eventsManagerClass = EventManager::class;

    /**
     * Error handler outputs.
     *
     * @var list<class-string<Error\Writer\Writable>>
     */
    protected array $errorHandlerLvl = [Error\Writer\Phplog::class, Error\Writer\Logger::class, Error\Writer\Flash::class, Error\Writer\View::class];

    public function registerRoutes(): void
    {
        if (is_file(BASE_PATH . RouteCompiler::COMPILED_FILE)) {
            require BASE_PATH . RouteCompiler::COMPILED_FILE;
        } else {
            require BASE_PATH . '/routes/http.php';
        }
    }

    public function boot(): void
    {
        $this->bootKernel();

        /** @var \Phalcon\Config\Config $config */
        $config = $this->getDI()->getShared(Services::CONFIG);

        $this->useImplicitView((bool) $config->path('view.implicit', false));
    }

    public function handleIncoming(): mixed
    {
        return $this->handle($this->incomingUri());
    }
}

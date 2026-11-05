<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Http;

use Neutrino\Constants\Services;
use Neutrino\Error;
use Neutrino\Foundation\Kernelize;
use Neutrino\Interfaces\Kernelable;
use Phalcon\Di\FactoryDefault as Di;
use Phalcon\Events\Manager as EventManager;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Application;

/**
 * Base class of the application's HTTP kernel.
 */
abstract class Kernel extends Application implements Kernelable
{
    use Kernelize {
        boot as private bootKernel;
    }

    /** The view was started for the actions to render into it (see startView()) */
    private bool $viewStarted = false;

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
        // A view built by a previous request (tests, long-running workers) is started again.
        $di = $this->container;
        if ($di !== null && $di->has(Services::VIEW) && $di->getService(Services::VIEW)->isResolved()) {
            /** @var \Phalcon\Mvc\View $view */
            $view = $di->getShared(Services::VIEW);
            $this->startView($view);
        }

        $response = $this->handle($this->incomingUri());

        $this->renderedView($response);

        return $response;
    }

    /**
     * Without implicit views, starts the view (called by the View provider when it builds it): the actions render
     * into it, and its content goes to the response.
     *
     * @internal
     */
    public function startView(\Phalcon\Mvc\View $view): void
    {
        if ($this->implicitView || $this->viewStarted) {
            return;
        }

        $view->start();
        $this->viewStarted = true;
    }

    /**
     * Without implicit views, the actions render with $this->view->render(): the View provider started the view,
     * its content goes to the response, unless the action set the content of the response itself. Called by
     * handleIncoming(), and by the Debugger before the debug bar reads the response. Not an events listener: any
     * listener on the application events costs about 20 µs per request.
     *
     * @internal
     */
    public function renderedView(mixed $response): void
    {
        $di = $this->container;

        if (!$this->viewStarted || $di === null) {
            return;
        }

        $this->viewStarted = false;

        /** @var \Phalcon\Mvc\View $view */
        $view = $di->getShared(Services::VIEW);
        $view->finish();

        if ($response instanceof ResponseInterface && $response->getContent() === '') {
            $response->setContent((string) $view->getContent());
        }
    }
}

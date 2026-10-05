<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Micro;

use Neutrino\Error;
use Neutrino\Foundation\Kernelize;
use Neutrino\Interfaces\Kernelable;
use Neutrino\Micro\Middleware;
use Phalcon\Di\FactoryDefault as Di;
use Phalcon\Mvc\Micro as MicroKernel;
use RuntimeException;

/**
 * Base class of the application's Micro kernel.
 */
abstract class Kernel extends MicroKernel implements Kernelable
{
    use Kernelize;

    /**
     * Providers to register.
     *
     * @var array<int|string, string>
     */
    protected array $providers = [];

    /**
     * Middlewares to bind to the application.
     *
     * @var list<class-string<Middleware>>
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
    protected ?string $eventsManagerClass = null;

    /**
     * Error handler outputs.
     *
     * @var list<class-string<Error\Writer\Writable>>
     */
    protected array $errorHandlerLvl = [Error\Writer\Phplog::class, Error\Writer\Logger::class, Error\Writer\Json::class];

    public function registerMiddlewares(): void
    {
        foreach ($this->middlewares as $middleware) {
            $this->registerMiddleware(new $middleware());
        }
    }

    protected function registerMiddleware(Middleware $middleware): void
    {
        match ($on = $middleware->bindOn()) {
            'before' => $this->before($middleware),
            'after' => $this->after($middleware),
            'finish' => $this->finish($middleware), // @phpstan-ignore argument.type (Micro middlewares are ported by E5)
            default => throw new RuntimeException(__METHOD__ . ': ' . $middleware::class . ' can\'t bind on "' . $on . '"'),
        };
    }

    /**
     * Micro applications have no modules.
     *
     * @param array<string, array{className?: string, path?: string}|\Closure> $modules
     */
    final public function registerModules(array $modules = [], bool $merge = false): static
    {
        return $this;
    }

    public function registerRoutes(): void
    {
        require BASE_PATH . '/routes/micro.php';
    }

    public function handleIncoming(): mixed
    {
        return $this->handle($this->incomingUri());
    }
}

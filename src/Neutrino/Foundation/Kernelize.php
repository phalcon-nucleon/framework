<?php

declare(strict_types=1);

namespace Neutrino\Foundation;

use Neutrino\Constants\Events\Kernel as KernelEvents;
use Neutrino\Constants\Services;
use Neutrino\Error\Handler;
use Neutrino\Events\Listener;
use Neutrino\Support\Facades\Facade;
use Phalcon\Config\Config;
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Events\ManagerInterface;
use RuntimeException;

/**
 * Boot logic shared by the HTTP, CLI and Micro kernels.
 *
 * The using class declares `$providers`, `$middlewares`, `$listeners`, `$dependencyInjection`,
 * `$eventsManagerClass` and `$errorHandlerLvl`.
 */
trait Kernelize
{
    /**
     * The providers of the kernel, as declared.
     *
     * @return array<int|string, string>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    public function registerServices(): void
    {
        ProviderRegistrar::register($this->getDI(), $this->providers);
    }

    public function registerMiddlewares(): void
    {
        foreach ($this->middlewares as $middleware) {
            $this->attach(new $middleware());
        }
    }

    public function registerListeners(): void
    {
        foreach ($this->listeners as $listener) {
            $this->attach(new $listener());
        }
    }

    /**
     * @param array<string, array{className?: string, path?: string}|\Closure> $modules
     */
    public function registerModules(array $modules, bool $merge = false): static
    {
        if ($this->modules !== [] || $modules !== []) {
            parent::registerModules(array_merge($this->modules, $modules), $merge);
        }

        return $this;
    }

    /**
     * Attaches a listener (or a middleware) to the kernel's events manager.
     */
    public function attach(Listener $listener): void
    {
        $eventsManager = $this->getEventsManager();

        if ($eventsManager === null) {
            throw new RuntimeException(static::class . ' has no events manager: set $eventsManagerClass to attach ' . $listener::class . '.');
        }

        $listener->setDI($this->getDI());
        $listener->setEventsManager($eventsManager);
        $listener->attach();
    }

    public function bootstrap(Config $config): void
    {
        Handler::setWriters($this->errorHandlerLvl);

        $diClass = $this->dependencyInjection;

        if ($diClass === null) {
            $di = Di::getDefault() ?? throw new RuntimeException('No default container: set ' . static::class . '::$dependencyInjection.');
        } else {
            Di::reset();

            /** @var DiInterface $di */
            $di = new $diClass();

            Di::setDefault($di);
        }

        $this->setDI($di);

        $di->setShared(Services::APP, $this);
        $di->setShared(Services::CONFIG, $config);

        $emClass = $this->eventsManagerClass;

        if ($emClass !== null) {
            /** @var ManagerInterface $em */
            $em = new $emClass();

            $this->setEventsManager($em);

            if ($di instanceof Di) {
                $di->setInternalEventsManager($em);
            }

            $di->setShared(Services::EVENTS_MANAGER, $em);
        }

        Facade::setDependencyInjection($di);
    }

    public function boot(): void
    {
        $this->getEventsManager()?->fire(KernelEvents::BOOT, $this);
    }

    public function terminate(): void
    {
        $this->getEventsManager()?->fire(KernelEvents::TERMINATE, $this);
    }

    /**
     * Path of the current request URI, without the query string.
     */
    protected function incomingUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        if (!is_string($uri) || $uri === '') {
            return '/';
        }

        return explode('?', $uri, 2)[0];
    }
}

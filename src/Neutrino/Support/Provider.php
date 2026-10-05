<?php

declare(strict_types=1);

namespace Neutrino\Support;

use Neutrino\Interfaces\Providable;
use Phalcon\Di\Injectable;
use Phalcon\Di\Service;
use RuntimeException;

/**
 * Registers a service built by {@see Provider::register()}, called on the first resolution only.
 *
 * Declare the real return type of `register()` (e.g. `register(): \Phalcon\Mvc\Router`):
 * the IDE helpers read it to document the service without building it.
 *
 * @property-read \Neutrino\Foundation\Http\Kernel|\Neutrino\Foundation\Cli\Kernel|\Neutrino\Foundation\Micro\Kernel $application
 * @property-read \Phalcon\Config\Config                                                                               $config
 */
abstract class Provider extends Injectable implements Providable
{
    /**
     * Name of the service.
     */
    protected string $name;

    /**
     * Other names the service is registered under (usually its class).
     *
     * @var list<string>
     */
    protected array $aliases = [];

    protected bool $shared = false;

    final public function __construct()
    {
        if (!isset($this->name) || $this->name === '') {
            throw new RuntimeException('Provider "' . static::class . '::$name" isn\'t valid.');
        }
    }

    final public function registering(): void
    {
        $self = $this;

        // Phalcon binds closure definitions to the container: $this would be the Di, not the provider.
        $service = new Service(function () use ($self) {
            return $self->register();
        }, $this->shared);

        $di = $this->getDI();
        $di->setService($this->name, $service);

        foreach ($this->aliases as $alias) {
            $di->setService($alias, $service);
        }
    }

    /**
     * Builds the service. Called when the container resolves the service.
     *
     * Not typed here so that each provider can declare the type of its service.
     *
     * @return mixed
     */
    abstract protected function register();
}

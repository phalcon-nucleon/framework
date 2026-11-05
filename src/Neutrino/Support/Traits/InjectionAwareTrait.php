<?php

declare(strict_types=1);

namespace Neutrino\Support\Traits;

use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use RuntimeException;

/**
 * Container access for classes that cannot extend {@see \Phalcon\Di\Injectable}:
 * `$this->{service}` resolves the shared service from the container.
 *
 * @property-read \Neutrino\Foundation\Http\Kernel|\Neutrino\Foundation\Cli\Kernel|\Neutrino\Foundation\Micro\Kernel $application
 * @property-read \Phalcon\Config\Config                                                                               $config
 */
trait InjectionAwareTrait
{
    protected ?DiInterface $container = null;

    /**
     * Services already resolved through {@see InjectionAwareTrait::__get()}.
     *
     * @var array<string, mixed>
     */
    private array $injectedServices = [];

    public function setDI(DiInterface $container): void
    {
        $this->container = $container;
    }

    public function getDI(): DiInterface
    {
        return $this->container ??= Di::getDefault() ?? throw new RuntimeException('No dependency injection container available.');
    }

    public function __get(string $name): mixed
    {
        if (array_key_exists($name, $this->injectedServices)) {
            return $this->injectedServices[$name];
        }

        $di = $this->getDI();

        if (!$di->has($name)) {
            throw new RuntimeException("$name not found in dependency injection.");
        }

        return $this->injectedServices[$name] = $di->getShared($name);
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->injectedServices) || $this->getDI()->has($name);
    }
}

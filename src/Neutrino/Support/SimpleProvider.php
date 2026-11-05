<?php

declare(strict_types=1);

namespace Neutrino\Support;

use Neutrino\Interfaces\Providable;
use Phalcon\Di\Injectable;
use Phalcon\Di\Service;
use RuntimeException;

/**
 * Registers `$class` as a service definition, built by the container on the first resolution.
 *
 * @property-read \Neutrino\Foundation\Http\Kernel|\Neutrino\Foundation\Cli\Kernel|\Neutrino\Foundation\Micro\Kernel $application
 * @property-read \Phalcon\Config\Config                                                                               $config
 */
abstract class SimpleProvider extends Injectable implements Providable
{
    /**
     * Class to provide.
     *
     * @var class-string
     */
    protected string $class;

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

    /**
     * Options of the Phalcon definition (`arguments`, `calls`, `properties`).
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    final public function __construct()
    {
        if (!isset($this->name) || $this->name === '') {
            throw new RuntimeException('Provider "' . static::class . '::$name" isn\'t valid.');
        }
        if (!isset($this->class)) {
            throw new RuntimeException('Provider "' . static::class . '::$class" isn\'t valid.');
        }
    }

    final public function registering(): void
    {
        $definition = $this->options === []
            ? $this->class
            : ['className' => $this->class] + $this->options;

        $service = new Service($definition, $this->shared);

        $di = $this->getDI();
        $di->setService($this->name, $service);

        foreach ($this->aliases as $alias) {
            $di->setService($alias, $service);
        }
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }
}

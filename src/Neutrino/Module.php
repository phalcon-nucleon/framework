<?php

declare(strict_types=1);

namespace Neutrino;

use Neutrino\Foundation\ProviderRegistrar;
use Phalcon\Di\DiInterface;
use Phalcon\Di\Injectable;
use Phalcon\Mvc\ModuleDefinitionInterface;

/**
 * Base class of an application module: registers its `$providers`, then calls {@see Module::initialise()}.
 *
 * @property-read \Neutrino\Foundation\Http\Kernel|\Neutrino\Foundation\Cli\Kernel $application
 * @property-read \Phalcon\Config\Config                                            $config
 */
class Module extends Injectable implements ModuleDefinitionInterface
{
    /**
     * Providers of the module, in the same format as the kernel's.
     *
     * @var array<int|string, string>
     */
    protected array $providers = [];

    public function registerAutoloaders(?DiInterface $container = null): void {}

    public function registerServices(DiInterface $container): void
    {
        ProviderRegistrar::register($container, $this->providers);

        $this->initialise($container);
    }

    /**
     * Called once the module's providers are registered.
     */
    public function initialise(DiInterface $container): void {}
}

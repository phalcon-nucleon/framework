<?php

declare(strict_types=1);

namespace Neutrino\Interfaces;

use Phalcon\Config\Config;

/**
 * A Nucleon kernel: a Phalcon application configured declaratively and started by
 * {@see \Neutrino\Foundation\Bootstrap}.
 */
interface Kernelable
{
    /**
     * Sets up the container, the events manager and the Facades.
     */
    public function bootstrap(Config $config): void;

    /**
     * Registers the `$providers`. Services are built when first resolved.
     */
    public function registerServices(): void;

    public function registerRoutes(): void;

    public function registerMiddlewares(): void;

    public function registerListeners(): void;

    /**
     * @param array<string, array{className?: string, path?: string}|\Closure> $modules
     */
    public function registerModules(array $modules, bool $merge = false): static;

    /**
     * Fires `kernel:boot`.
     */
    public function boot(): void;

    /**
     * Handles the current input (the request URI, or the command line arguments)
     * and returns what the Phalcon `handle()` returns.
     */
    public function handleIncoming(): mixed;

    /**
     * Fires `kernel:terminate`.
     */
    public function terminate(): void;
}

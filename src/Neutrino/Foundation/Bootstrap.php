<?php

declare(strict_types=1);

namespace Neutrino\Foundation;

use Neutrino\Constants\Env;
use Neutrino\Debug\Debugger;
use Neutrino\Interfaces\Kernelable;
use Phalcon\Config\Config;
use Phalcon\Http\ResponseInterface;

/**
 * Builds a kernel and runs it.
 */
final class Bootstrap
{
    public function __construct(private readonly Config $config) {}

    /**
     * @template T of Kernelable
     *
     * @param class-string<T> $kernelClass
     *
     * @return T
     */
    public function make(string $kernelClass): Kernelable
    {
        $kernel = new $kernelClass();

        $kernel->bootstrap($this->config);

        if (APP_DEBUG && APP_ENV !== Env::TEST && PHP_SAPI !== 'cli') {
            Debugger::register();
        }

        $kernel->registerServices();
        $kernel->registerMiddlewares();
        $kernel->registerListeners();
        $kernel->registerRoutes();
        $kernel->registerModules([]);

        return $kernel;
    }

    public function run(Kernelable $kernel): void
    {
        $kernel->boot();

        $response = $kernel->handleIncoming();

        if ($response instanceof ResponseInterface && !$response->isSent()) {
            $response->send();
        }

        $kernel->terminate();
    }
}

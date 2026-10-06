<?php

declare(strict_types=1);

namespace Neutrino\Foundation;

use Neutrino\Constants\Env;
use Neutrino\Debug\Debugger;
use Neutrino\Error\Handler;
use Neutrino\Interfaces\Kernelable;
use Phalcon\Config\Config;
use Phalcon\Http\ResponseInterface;

/**
 * Builds a kernel and runs it.
 *
 * Outside the tests (`APP_ENV`), it registers the error handler ({@see Handler}, unless `error.register` is
 * `false`) and, when `APP_DEBUG` is true, the debug mode of the HTTP and Micro kernels ({@see Debugger}).
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

        // Not in the tests: the handlers would replace PHPUnit's. 'test' is Env::TEST, without loading the class.
        if (APP_ENV !== 'test' && $this->config->path('error.register', true) !== false) {
            Handler::register();
        }

        $kernel->registerServices();

        if (APP_DEBUG && APP_ENV !== Env::TEST && PHP_SAPI !== 'cli') {
            Debugger::register($kernel);
        }

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

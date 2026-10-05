<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli;

use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Output\Helper;
use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;
use Neutrino\Error;
use Neutrino\Foundation\Cli\Tasks\HelperTask;
use Neutrino\Foundation\Kernelize;
use Neutrino\Interfaces\Kernelable;
use Phalcon\Cli\Console;
use Phalcon\Cli\Router\Route;
use Phalcon\Di\FactoryDefault\Cli as Di;
use Phalcon\Events\Manager as EventManager;

/**
 * Base class of the application's CLI kernel.
 *
 * @property-read \Neutrino\Cli\Router    $router
 * @property-read \Phalcon\Cli\Dispatcher $dispatcher
 */
abstract class Kernel extends Console implements Kernelable
{
    use Kernelize {
        boot as private bootKernel;
        terminate as private terminateKernel;
    }

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
    protected array $errorHandlerLvl = [Error\Writer\Phplog::class, Error\Writer\Logger::class, Error\Writer\Cli::class];

    public function registerRoutes(): void
    {
        require BASE_PATH . '/routes/cli.php';
    }

    /**
     * @param array<int|string, mixed>|null $arguments
     */
    public function handle(?array $arguments = null): mixed
    {
        if ($arguments !== null && $arguments !== []) {
            $this->setArgument($arguments, false, false);
        }

        if ($this->isHelp()) {
            $this->arguments = [
                'task'   => HelperTask::class,
                'action' => 'main',
                'params' => [
                    'arguments' => $this->arguments,
                ],
            ];
        }

        return parent::handle();
    }

    /**
     * Handles the command line (`$_SERVER['argv']`), unless arguments were already set with setArgument().
     */
    public function handleIncoming(): mixed
    {
        if ($this->arguments === [] || $this->arguments === '') {
            $argv = $_SERVER['argv'] ?? [];
            $this->setArgument(is_array($argv) ? array_values(array_map(static fn(mixed $arg): string => is_scalar($arg) ? (string) $arg : '', $argv)) : []);
        }

        return $this->handle();
    }

    /**
     * @return list<string>|string|array<int|string, mixed>
     */
    public function getArguments(bool $raw = false): array|string
    {
        if ($raw || !is_string($this->arguments)) {
            return $this->arguments;
        }

        return explode(Route::getDelimiter() ?: ' ', $this->arguments);
    }

    public function boot(): void
    {
        $this->bootKernel();

        if (isset($this->options['no-colors'])) {
            Decorate::setColorSupport(false);
        } elseif (isset($this->options['colors'])) {
            Decorate::setColorSupport(true);
        }
    }

    public function terminate(): void
    {
        $this->terminateKernel();

        if ($this->withStats()) {
            $this->displayStats();
        }

        if ($this->getDI()->has(Services\Cli::OUTPUT)) {
            $this->output()->clean();
        }
    }

    public function isQuiet(): bool
    {
        return isset($this->options['q']) || isset($this->options['quiet']);
    }

    public function isHelp(): bool
    {
        return isset($this->options['h']) || isset($this->options['help']);
    }

    public function withStats(): bool
    {
        return isset($this->options['s']) || isset($this->options['stats']);
    }

    public function displayStats(): void
    {
        $start = $_SERVER['REQUEST_TIME_FLOAT'] ?? null;

        $output = $this->output();
        $output->line('');
        $output->line('Stats : ');
        $output->line("\tmem:" . Decorate::info((string) memory_get_usage()));
        $output->line("\tmem.peak:" . Decorate::info((string) memory_get_peak_usage()));
        $output->line("\ttime:" . Decorate::info((string) (microtime(true) - (is_float($start) ? $start : microtime(true)))));
    }

    public function displayNeutrinoVersion(): void
    {
        $this->output()->write(Helper::neutrinoVersion() . PHP_EOL, true);
    }

    private function output(): Writer
    {
        /** @var Writer */
        return $this->getDI()->getShared(Services\Cli::OUTPUT);
    }
}

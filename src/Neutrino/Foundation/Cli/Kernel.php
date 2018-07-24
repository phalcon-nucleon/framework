<?php

namespace Neutrino\Foundation\Cli;

use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Output\Helper;
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
 * Class Cli
 *
 * @package Neutrino\Foundation\Kernel
 *
 * @property-read \Neutrino\Cli\Router    $router
 * @property-read \Phalcon\Cli\Dispatcher $dispatcher
 */
abstract class Kernel extends Console implements Kernelable
{
    use Kernelize {
        boot as _boot;
        terminate as _terminate;
    }

    /**
     * Return the Provider List to load.
     *
     * @var string[]
     */
    protected $providers = [];

    /**
     * Return the Middlewares to attach onto the application.
     *
     * @var string[]
     */
    protected $middlewares = [];

    /**
     * Return the Events Listeners to attach onto the application.
     *
     * @var string[]
     */
    protected $listeners = [];

    /**
     * Return the modules to attach onto the application.
     *
     * @var string[]
     */
    protected $modules = [];

    /**
     * The DependencyInjection class to use.
     *
     * @var string
     */
    protected $dependencyInjection = Di::class;

    /**
     * The EventManager class to use.
     *
     * @var string
     */
    protected $eventsManagerClass = EventManager::class;

    /**
     * Error Handler Outputs
     *
     * @var int
     */
    protected $errorHandlerLvl = [Error\Writer\Phplog::class, Error\Writer\Logger::class, Error\Writer\Cli::class];

    /**
     * Application constructor.
     */
    public function __construct()
    {
        parent::__construct(null);
    }

    /**
     * Register the routes of the application.
     */
    public function registerRoutes()
    {
        require BASE_PATH . '/routes/cli.php';
    }

    public function handle(array $arguments = null)
    {
        if (!empty($arguments)) {
            // In php5.6, setArgument will modify the $ arguments variable, even if it is not passed by reference.
            // We therefore clone the contents of $ arguments to converse the data.
            $this->setArgument(array_merge([], $arguments), false, false);
        }

        if ($this->isHelp()) {
            $this->_arguments = [
                'task'   => HelperTask::class,
                'action' => 'main',
                'params' => [
                    'arguments' => $this->_arguments,
                ]
            ];
        }

        parent::handle($arguments);
    }

    public function getArguments($raw = false)
    {
        if ($raw) {
            return $this->_arguments;
        } else {
            return explode(Route::getDelimiter(), $this->_arguments);
        }
    }

    public function boot()
    {
        $this->_boot();

        if (isset($this->_options['no-colors'])) {
            Decorate::setColorSupport(false);
        } elseif (isset($this->_options['colors'])) {
            Decorate::setColorSupport(true);
        }
    }

    public function terminate()
    {
        $this->_terminate();

        if ($this->withStats()) {
            $this->displayStats();
        }

        if ($this->getDI()->has(Services\Cli::OUTPUT)) {
            $this->{Services\Cli::OUTPUT}->clean();
        };
    }

    public function isQuiet()
    {
        return isset($this->_options['q']) || isset($this->_options['quiet']);
    }

    public function isHelp()
    {
        return isset($this->_options['h']) || isset($this->_options['help']);
    }

    public function withStats()
    {
        return isset($this->_options['s']) || isset($this->_options['stats']);
    }

    public function displayStats()
    {
        /** @var \Neutrino\Cli\Output\Writer $output */
        $output = $this->{Services\Cli::OUTPUT};
        $output->line('');
        $output->line('Stats : ');
        $output->line("\tmem:" . Decorate::info(memory_get_usage()));
        $output->line("\tmem.peak:" . Decorate::info(memory_get_peak_usage()));
        $output->line("\ttime:" . Decorate::info((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'])));
    }

    public function displayNeutrinoVersion()
    {
        $this->{Services\Cli::OUTPUT}->write(Helper::neutrinoVersion() . PHP_EOL, true);
    }
}

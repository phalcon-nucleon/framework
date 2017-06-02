<?php

namespace Neutrino\Cli;

use Neutrino\Cli\Output\ConsoleOutput;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Output\Helper;
use Neutrino\Cli\Output\QuestionHelper;
use Neutrino\Cli\Output\Table;
use Neutrino\Cli\Question\ChoiceQuestion;
use Neutrino\Cli\Question\ConfirmationQuestion;
use Neutrino\Cli\Question\Question;
use Neutrino\Constants\Events;
use Neutrino\Error\Handler;
use Neutrino\Foundation\Cli\Tasks\HelperTask;
use Neutrino\Support\Arr;
use Phalcon\Cli\Task as PhalconTask;
use Phalcon\Events\Event;

/**
 * Class Task
 *
 * @package Neutrino\Cli
 *
 * @property-read \Phalcon\Application|\Phalcon\Mvc\Application|\Phalcon\Cli\Console|\Phalcon\Mvc\Micro $application
 * @property-read \Phalcon\Config|\stdClass|\ArrayAccess                                                $config
 * @property-read \Neutrino\Cli\Router                                                                  $router
 * @property-read \Phalcon\Cli\Dispatcher                                                               $dispatcher
 */
abstract class Task extends PhalconTask
{
    /**
     * @var ConsoleOutput
     */
    protected $output;

    public function onConstruct()
    {
        $this->output = new ConsoleOutput($this->hasOption('q', 'quiet'));

        $em = $this->dispatcher->getEventsManager();

        $em->attach(Events\Dispatch::BEFORE_EXCEPTION, function (Event $event, $dispatcher, \Exception $exception) {
            return $this->handleException($exception);
        });

        if (($this->hasOption('s', 'stats')) && !$this->dispatcher->wasForwarded()) {
            $em->attach(Events\Cli\Application::AFTER_HANDLE, function () {
                $this->displayStats();
            });
        }

        $em->attach(Events\Cli\Application::AFTER_HANDLE, function () {
            $this->output->clean();
        });
    }

    /**
     * Handle help option & forward to HelperTask
     *
     * @return bool
     */
    public function beforeExecuteRoute()
    {
        if ($this->hasOption('h', 'help')) {
            $this->dispatcher->forward([
                'task'   => HelperTask::class,
                'action' => 'main',
                'params' => [
                    'task'   => $this->dispatcher->getHandlerClass(),
                    'action' => $this->dispatcher->getActionName(),
                ]
            ]);

            return false;
        }

        return true;
    }

    /**
     * Handle Exception and output them.
     *
     * @param \Exception $exception
     *
     * @return bool
     */
    public function handleException(\Exception $exception)
    {
        $this->error('Exception : ' . get_class($exception));
        $this->error($exception->getMessage());
        $this->error($exception->getTraceAsString());

        Handler::handleException($exception);

        return false;
    }

    public function displayStats()
    {
        $this->line('');
        $this->line('Stats : ');
        $this->line("\tmem:" . Decorate::info(memory_get_usage()));
        $this->line("\tmem.peak:" . Decorate::info(memory_get_peak_usage()));
        $this->line("\ttime:" . Decorate::info((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'])));
    }

    public function displayNeutrinoVersion()
    {
        $this->output->write(Helper::neutrinoVersion() . PHP_EOL, true);
    }

    /**
     * @param string $str
     */
    public function line($str)
    {
        $this->output->write($str, true);
    }

    /**
     * @param string $str
     */
    public function info($str)
    {
        $this->output->info($str);
    }

    /**
     * @param string $str
     */
    public function notice($str)
    {
        $this->output->notice($str);
    }

    /**
     * @param string $str
     */
    public function warn($str)
    {
        $this->output->warn($str);
    }

    /**
     * @param string $str
     */
    public function error($str)
    {
        $this->output->error($str);
    }

    /**
     * @param string $str
     */
    public function question($str)
    {
        $this->output->question($str);
    }

    /**
     * @param string      $str
     * @param null|string $default
     *
     * @return null|string
     */
    public function prompt($str, $default = null)
    {
        return $this->ask(new Question($str, $default));
    }

    /**
     * @param string $str
     * @param bool   $default
     *
     * @return null|string
     */
    public function confirm($str, $default = false)
    {
        return $this->ask(new ConfirmationQuestion($str, $default));
    }

    /**
     * @param string      $str
     * @param array       $choices
     * @param int         $maxAttempts
     * @param null|string $default
     *
     * @return null|string
     */
    public function choices($str, array $choices, $maxAttempts = 0, $default = null)
    {
        return $this->ask(new ChoiceQuestion($str, $choices, $maxAttempts, $default));
    }

    /**
     * @param \Neutrino\Cli\Question\Question $question
     *
     * @return null|string
     */
    public function ask(Question $question)
    {
        return QuestionHelper::ask($this->output, STDIN, $question);
    }

    /**
     * @param array $datas
     * @param array $headers
     * @param int   $style Table::STYLE_DEFAULT | Table::NO_STYLE | ...
     */
    public function table(array $datas, array $headers = [], $style = Table::STYLE_DEFAULT)
    {
        (new Table($this->output, $datas, $headers, $style))->display();
    }


    /**
     * Return all agruments pass to the cli
     *
     * @return array
     */
    protected function getArgs()
    {
        return $this->dispatcher->getParams();
    }

    /**
     * Return an arg by his name, or default
     *
     * @param string     $name
     * @param mixed|null $default
     *
     * @return array
     */
    protected function getArg($name, $default = null)
    {
        return Arr::fetch($this->getArgs(), $name, $default);
    }

    /**
     * Check if arg has been passed
     *
     * @param string $name
     *
     * @return bool
     */
    protected function hasArg($name)
    {
        return Arr::has($this->getArgs(), $name);
    }

    /**
     * Return all options pass to the cli
     *
     * @return array
     */
    protected function getOptions()
    {
        return $this->dispatcher->getOptions();
    }

    /**
     * Return an option by his name, or default
     *
     * @param string     $name
     * @param mixed|null $default
     *
     * @return array
     */
    protected function getOption($name, $default = null)
    {
        return Arr::fetch($this->getOptions(), $name, $default);
    }

    /**
     * Check if option has been passed
     *
     * @param string[] ...$options
     *
     * @return bool
     */
    protected function hasOption(...$options)
    {
        foreach ($options as $option) {
            if (Arr::has($this->getOptions(), $option)) {
                return true;
            }
        }

        return false;
    }
}

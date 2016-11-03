<?php

namespace Luxury\Cli;

use Luxury\Cli\Output\ConsoleOutput;
use Luxury\Cli\Output\Table;
use Luxury\Support\Arr;
use Phalcon\Cli\Task as PhalconTask;

/**
 * Class Task
 *
 * @package Luxury\Cli
 *
 * @property-read \Luxury\Cli\Router $router
 * @property-read \Phalcon\Cli\Dispatcher $dispatcher
 */
class Task extends PhalconTask
{
    /**
     * @var ConsoleOutput
     */
    protected $output;

    public function onConstruct()
    {
        $this->output = new ConsoleOutput($this->hasOption('q') || $this->hasOption('quiet'));
    }

    public function info($str)
    {
        $this->line($this->output->info($str));
    }

    public function notice($str)
    {
        $this->line($this->output->notice($str));
    }

    public function warn($str)
    {
        $this->line($this->output->warn($str));
    }

    public function error($str)
    {
        $this->line($this->output->error($str));
    }

    public function question($str)
    {
        $this->line($this->output->question($str));
    }

    public function table(array $datas, array $headers = [], $style = Table::STYLE_DEFAULT)
    {
        (new Table($this->output, $datas, $headers, $style))->display();
    }

    public function line($str)
    {
        $this->output->write($str, true);
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
     * @param string $name
     *
     * @return bool
     */
    protected function hasOption($name)
    {
        return Arr::has($this->getOptions(), $name);
    }
}

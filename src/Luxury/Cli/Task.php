<?php

namespace Luxury\Cli;

use Luxury\Support\Arr;
use Phalcon\Cli\Task as PhalconTask;

/**
 * Class Task
 *
 * @package Luxury\Cli
 *
 * @property-read \Phalcon\Cli\Dispatcher $dispatcher
 * @property-read \Phalcon\Cli\Router     $router
 */
class Task extends PhalconTask
{
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

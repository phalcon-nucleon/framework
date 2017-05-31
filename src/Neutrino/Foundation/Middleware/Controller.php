<?php

namespace Neutrino\Foundation\Middleware;

use Neutrino\Constants\Events;
use Neutrino\Events\Listener;
use Neutrino\Interfaces\Middleware\AfterInterface;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Neutrino\Interfaces\Middleware\FinishInterface;

/**
 * ControllerMiddleware
 *
 * Class Controller
 *
 *  @package Neutrino\Foundation\Middleware
 */
abstract class Controller extends Listener
{
    /**
     * Filter methods
     *
     * @var array
     */
    private $filter = [];

    /**
     * The controller who create this middleware
     *
     * @var string
     */
    private $controllerClass;

    /**
     * ControllerMiddleware constructor.
     *
     * @param string $controllerClass The controller who create this middleware
     */
    public function __construct($controllerClass)
    {
        $this->controllerClass = $controllerClass;

        if ($this instanceof BeforeInterface) {
            $this->listen[Events\Dispatch::BEFORE_EXECUTE_ROUTE] = 'checkBefore';
        }
        if ($this instanceof AfterInterface) {
            $this->listen[Events\Dispatch::AFTER_EXECUTE_ROUTE] = 'checkAfter';
        }
        if ($this instanceof FinishInterface) {
            $this->listen[Events\Dispatch::AFTER_DISPATCH] = 'checkFinish';
        }
    }

    /**
     * @return bool
     */
    final public function check()
    {
        $dispatcher = $this->dispatcher;

        if($dispatcher->wasForwarded() && !$dispatcher->isFinished()){
            // Controller has just been forwarded
            return false;
        }

        if ($this->controllerClass !== $dispatcher->getHandlerClass()) {
            return false;
        }

        $action = $dispatcher->getActionName();

        $enable = true;
        if (isset($this->filter['only'])) {
            $enable = isset($this->filter['only'][$action]);
        }

        if ($enable && isset($this->filter['except'])) {
            $enable = !isset($this->filter['except'][$action]);
        }

        return $enable;
    }

    /**
     * Allowed Method.
     *
     * @param array|null $filters
     *
     * @return \Neutrino\Foundation\Middleware\Controller
     */
    final public function only(array $filters = null)
    {
        return $this->filters('only', $filters);
    }

    /**
     * Excepted Method.
     *
     * @param array|null $filters
     *
     * @return \Neutrino\Foundation\Middleware\Controller
     */
    final public function except(array $filters = null)
    {
        return $this->filters('except', $filters);
    }

    /**
     * @param $event
     * @param $source
     * @param $data
     *
     * @return mixed
     */
    final public function checkBefore($event, $source, $data)
    {
        if ($this->check()) {
            /** @var \Neutrino\Interfaces\Middleware\BeforeInterface $this */
            return $this->before($event, $source, $data);
        }

        return true;
    }

    /**
     * @param $event
     * @param $source
     * @param $data
     *
     * @return mixed
     */
    final public function checkAfter($event, $source, $data)
    {
        if ($this->check()) {
            /** @var \Neutrino\Interfaces\Middleware\AfterInterface $this */
            return $this->after($event, $source, $data);
        }

        return true;
    }

    /**
     * @param $event
     * @param $source
     * @param $data
     *
     * @return mixed
     */
    final public function checkFinish($event, $source, $data)
    {
        if ($this->check()) {
            /** @var \Neutrino\Interfaces\Middleware\FinishInterface $this */
            return $this->finish($event, $source, $data);
        }

        return true;
    }

    /**
     * @param            $type
     * @param array|null $filters
     *
     * @return $this
     */
    private function filters($type, array $filters = null)
    {
        if ($filters === null) {
            return $this;
        }
        if (empty($filters)) {
            $this->filter[$type] = [];

            return $this;
        }

        foreach ($filters as $item) {
            $this->filter[$type][$item] = true;
        }

        return $this;
    }
}

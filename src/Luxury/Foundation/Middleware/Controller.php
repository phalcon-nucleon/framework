<?php

namespace Luxury\Foundation\Middleware;

use Luxury\Constants\Events;
use Luxury\Events\Listener;
use Luxury\Interfaces\Middleware\AfterInterface;
use Luxury\Interfaces\Middleware\BeforeInterface;
use Luxury\Interfaces\Middleware\FinishInterface;

/**
 * ControllerMiddleware
 *
 * Class Controller
 *
 * @package Luxury\Foundation\Middleware
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
        parent::__construct();

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

        if ($this->controllerClass !== $dispatcher->getHandlerClass()) {
            return false;
        }

        $action = $dispatcher->getActionName() . $dispatcher->getActionSuffix();

        $enable = true;
        if (isset($this->filter['only'])) {
            $enable = in_array($action, $this->filter['only']);
        }

        if ($enable && isset($this->filter['except'])) {
            $enable = !in_array($action, $this->filter['except']);
        }

        return $enable;
    }

    /**
     * Allowed Method.
     *
     * @param array|null $filters
     *
     * @return \Luxury\Foundation\Middleware\Controller
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
     * @return \Luxury\Foundation\Middleware\Controller
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
            /** @var \Luxury\Interfaces\Middleware\BeforeInterface $this */
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
            /** @var \Luxury\Interfaces\Middleware\AfterInterface $this */
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
            /** @var \Luxury\Interfaces\Middleware\FinishInterface $this */
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
            $this->filter[$type][] = $item;
        }

        return $this;
    }
}

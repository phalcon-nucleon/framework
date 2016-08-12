<?php

namespace Luxury\Foundation\Middleware;

use Luxury\Constants\Events;
use Luxury\Middleware\{
    AfterMiddleware,
    BeforeMiddleware,
    FinishMiddleware,
    Middleware
};

/**
 * ControllerMiddleware
 *
 * Class Controller
 *
 * @package Luxury\Foundation\Middleware
 */
abstract class Controller extends Middleware
{
    /**
     * Filter methods
     *
     * @var array
     */
    private $filter = [];

    /**
     * ControllerMiddleware constructor.
     */
    final public function __construct()
    {
        parent::__construct();

        if ($this instanceof BeforeMiddleware) {
            $this->listen[Events\Dispatch::BEFORE_EXECUTE_ROUTE] = 'checkBefore';
        }
        if ($this instanceof AfterMiddleware) {
            $this->listen[Events\Dispatch::AFTER_EXECUTE_ROUTE] = 'checkAfter';
        }
        if ($this instanceof FinishMiddleware) {
            $this->listen[Events\Dispatch::AFTER_DISPATCH] = 'checkFinish';
        }
    }

    /**
     * @return bool
     */
    final public function check()
    {
        $dispatcher = $this->dispatcher;

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
            /** @var BeforeMiddleware $this */
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
            /** @var AfterMiddleware $this */
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
            /** @var FinishMiddleware $this */
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
        if ($filters == null) {
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

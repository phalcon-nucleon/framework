<?php

namespace Luxury\Foundation\Middleware;

use Luxury\Constants\Events;
use Luxury\Middleware\AfterMiddleware;
use Luxury\Middleware\BeforeMiddleware;
use Luxury\Middleware\FinishMiddleware;
use Luxury\Middleware\Middleware;

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
     *
     * @param array $params [only => [methodAllowed], except => [notAllowed]]
     */
    public function __construct(array $params = [])
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
    public function check()
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
    public function only(array $filters = null)
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
    public function except(array $filters = null)
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
    public function checkBefore($event, $source, $data)
    {
        if ($this->check()) {
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
    public function checkAfter($event, $source, $data)
    {
        if ($this->check()) {
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
    public function checkFinish($event, $source, $data)
    {
        if ($this->check()) {
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

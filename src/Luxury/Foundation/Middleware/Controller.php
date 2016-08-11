<?php

namespace Luxury\Foundation\Middleware;

use Luxury\Constants\Events;
use Luxury\Middleware\AfterMiddleware;
use Luxury\Middleware\BeforeMiddleware;
use Luxury\Middleware\FinishMiddleware;
use Luxury\Middleware\Middleware;
use Luxury\Support\Arr;

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

    private $enable = true;

    /**
     * ControllerMiddleware constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->listen[Events\Dispatch::BEFORE_DISPATCH] = 'init';

        if ($this instanceof BeforeMiddleware) {
            $this->listen[Events\Dispatch::BEFORE_EXECUTE_ROUTE] = 'callBefore';
        }
        if ($this instanceof AfterMiddleware) {
            $this->listen[Events\Dispatch::AFTER_EXECUTE_ROUTE] = 'callAfter';
        }
        if ($this instanceof FinishMiddleware) {
            $this->listen[Events\Dispatch::AFTER_DISPATCH] = 'callFinish';
        }
    }

    final public function init($event, $source, $data)
    {
        $action = $this->dispatcher->getActionName() . $this->dispatcher->getActionSuffix();

        $only = $this->filter['only'] ?? null;
        if (!empty($only)) {
            $this->enable = in_array($action, $only);

            return;
        }

        $except = $this->filter['except'] ?? null;

        if (!empty($except)) {
            $this->enable = !in_array($action, $except);
        }

        return;
    }

    final public function callBefore($event, $source, $data)
    {
        if ($this->enable) {
            return $this->before($event, $source, $data);
        }
    }

    final public function callAfter($event, $source, $data)
    {
        if ($this->enable) {
            return $this->after($event, $source, $data);
        }
    }

    final public function callFinish($event, $source, $data)
    {
        if ($this->enable) {
            return $this->finish($event, $source, $data);
        }
    }

    public function only(array $filters = null)
    {
        return $this->filters(__FUNCTION__, $filters);
    }

    public function except(array $filters = null)
    {
        return $this->filters(__FUNCTION__, $filters);
    }

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

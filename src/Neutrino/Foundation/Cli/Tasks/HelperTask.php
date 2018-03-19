<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Output\Helper;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Neutrino\Support\Arr;
use Phalcon\Cli\Router\Route;

/**
 * Class HelperTask
 *
 * @package Neutrino\Foundation\Cli
 */
class HelperTask extends Task
{
    public function mainAction()
    {
        $this->{Services::APP}->displayNeutrinoVersion();

        if ($this->hasArg('arguments')) {
            $this->router->handle($this->getArg('arguments'));

            if (!$this->router->wasMatched()) {
                if (is_null($route = $this->tryHandle($this->getArg('arguments')))) {
                    throw new \Exception('route not found');
                }
                $task   = Arr::fetch($route, 'task');
                $action = Arr::fetch($route, 'action', 'main') . $this->dispatcher->getActionSuffix();
            } else {
                $task   = $this->router->getTaskName();
                $action = ($this->router->getActionName() ?: 'main') . $this->dispatcher->getActionSuffix();
            }

        } else {
            $task   = $this->getArg('task');
            $action = $this->getArg('action') . $this->dispatcher->getActionSuffix();
        }

        $infos = Helper::getTaskInfos(
            $task,
            $action
        );

        $route = $this->resolveRoute($task, $action);

        if (!empty($route)) {
            $this->line('Usage :');
            $this->info("\t" . Helper::describeRoutePattern($route, true));
        }

        $this->line('Description :');
        $this->line("\t" . preg_replace('/' . PHP_EOL . '/', PHP_EOL . "\t", $infos['description']));

        if (Arr::has($infos, 'arguments')) {
            $this->line('Arguments :');
            foreach ($infos['arguments'] as $argument) {
                $this->line("\t" . $argument);
            }
        }
        if (Arr::has($infos, 'options')) {
            $this->line('Options :');
            foreach ($infos['options'] as $option) {
                $this->line("\t" . $option);
            }
        }
    }

    /**
     * @param $class
     * @param $action
     *
     * @return null|Route
     */
    private function resolveRoute($class, $action)
    {
        $routes = $this->router->getRoutes();

        $findedRoute = null;
        foreach ($routes as $route) {
            /** @var Route $route */

            $paths = $route->getPaths();

            if ($paths['task'] == $class) {
                if (Arr::fetch($paths, 'action', 'main') . $this->dispatcher->getActionSuffix() == $action) {
                    $findedRoute = $route;
                    break;
                }
            }
        }

        if (!empty($findedRoute)) {
            return $findedRoute;
        }

        return null;
    }

    private function tryHandle($arg)
    {
        $routes = $this->router->getRoutes();

        $findedRoute = null;
        foreach ($routes as $route) {
            /** @var Route $route */
            $pattern = $route->getCompiledPattern();

            do {
                $old     = $pattern;
                $pattern = preg_replace('/\([^\(\)]*\)(?:[+*]|\{[\d,]\})?/', '', $pattern);
            } while ($pattern !== $old);

            $pattern = trim(preg_replace('/ /', '\s*', $pattern));

            if (preg_match($pattern, trim($arg))) {

                return $route->getPaths();
            }
        }

        return null;
    }
}

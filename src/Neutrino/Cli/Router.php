<?php

namespace Neutrino\Cli;

/**
 * Class Router
 *
 *  @package Neutrino\Cli
 */
class Router extends \Phalcon\Cli\Router
{
    /**
     * Adds a route to the router, with task as class.
     *
     * ex : $router->addTask('some', SomeTask::class);
     *
     * @param string      $command
     * @param string      $class
     * @param string|null $action
     * @param array       $params
     *
     * @return \Phalcon\Cli\Router\Route|\Phalcon\Cli\Router\RouteInterface
     */
    public function addTask($command, $class, $action = null, array $params = [])
    {
        $params['task'] = $class;

        $params['action'] = $action;

        $params['_command'] = $command;

        preg_match_all('/\{([\w_]+)\}/', $command, $matches);

        foreach ($matches[0] as $k => $match) {
            $command = str_replace($match, '([[:alnum:]]+)', $command);

            $params[substr($match, 1, -1)] = $k + 1;
        }

        return $this->add($command, $params);
    }
}

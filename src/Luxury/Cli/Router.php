<?php

namespace Luxury\Cli;

/**
 * Class Router
 *
 * @package     Luxury\Cli
 */
class Router extends \Phalcon\Cli\Router
{
    /**
     * Adds a route to the router, with task as class.
     *
     * ex : $router->addTask('some', SomeTask::class);
     *
     * @param string      $pattern
     * @param string      $class
     * @param string|null $action
     * @param array       $params
     *
     * @return \Phalcon\Cli\Router\Route
     */
    public function addTask($pattern, $class, $action = null, array $params = [])
    {
        $params['task'] = $this->classToTask($class);

        $params['action'] = $action;

        preg_match_all('/:([\w_]+):/', $pattern, $matches);

        foreach ($matches[0] as $k => $match) {
            $pattern = str_replace($match, '([[:alnum:]]+)', $pattern);

            $params[str_replace(':', '', $match)] = $k + 1;
        }

        return $this->add($pattern, $params);
    }

    /**
     * Transform a task class name, to a valid task for the router.
     *
     * @param string $class
     *
     * @return string
     */
    public function classToTask($class)
    {
        return preg_replace('/Task$/', '', $class);
    }
}

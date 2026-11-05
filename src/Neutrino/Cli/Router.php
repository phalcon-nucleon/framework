<?php

declare(strict_types=1);

namespace Neutrino\Cli;

use Phalcon\Cli\Router\RouteInterface;

/**
 * Console router, with routes to task classes.
 */
class Router extends \Phalcon\Cli\Router
{
    /**
     * Adds a command handled by a task class. `{param}` parts become named parameters.
     *
     * ex : $router->addTask('make:migration {name}', MakerTask::class);
     *
     * @param class-string             $class
     * @param array<string, mixed>     $params
     */
    public function addTask(string $command, string $class, ?string $action = null, array $params = []): RouteInterface
    {
        $params['task'] = $class;
        if ($action !== null) {
            $params['action'] = $action;
        }

        preg_match_all('/\{(\w+)\}/', $command, $matches);

        foreach ($matches[0] as $position => $match) {
            $command = str_replace($match, '([[:word:]]+)', $command);

            $params[$matches[1][$position]] = $position + 1;
        }

        return $this->add($command, $params);
    }
}

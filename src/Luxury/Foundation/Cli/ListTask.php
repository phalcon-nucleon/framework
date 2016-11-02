<?php

namespace Luxury\Foundation\Cli;

use Luxury\Cli\Task;
use Luxury\Support\Arr;
use Luxury\Support\Str;

/**
 * Class ListTask
 *
 * @package     Luxury\Foundation\Cli
 */
class ListTask extends Task
{
    protected $reflections = [];
    protected $scanned     = [];

    public function mainAction()
    {
        $routes = $this->router->getRoutes();

        $actionSuffix = $this->dispatcher->getActionSuffix();

        $delimiter = \Phalcon\Cli\Router\Route::getDelimiter();
        foreach ($routes as $route) {
            /** @var \Phalcon\Cli\Router\Route $route */
            // Default route
            if ($route->getPattern() === "#^(?:$delimiter)?([a-zA-Z0-9\\_\\-]+)[$delimiter]{0,1}$#" ||
                $route->getPattern() === "#^(?:$delimiter)?([a-zA-Z0-9\\_\\-]+)$delimiter([a-zA-Z0-9\\.\\_]+)($delimiter.*)*$#"
            ) {
                continue;
            }

            $params = $route->getPaths();

            $this->describe(Str::lower($params['task']), Arr::fetch($params, 'action', 'main'));
        }

        $taskPath = $this->config->paths->app . 'Cli' . DIRECTORY_SEPARATOR . 'Tasks' . DIRECTORY_SEPARATOR;

        $files = scandir($taskPath);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $class     = str_replace('.php', '', $file);
            $fullClass = 'App\Cli\Tasks\\' . $class;
            echo $fullClass . PHP_EOL;

            $reflection = new \ReflectionClass($fullClass);

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $methodName = $method->getName();
                if (!Str::endsWith($methodName, $actionSuffix)) {
                    continue;
                }

                $task = Str::lower(substr($class, 0, strlen($class) - 4));
                $action     = substr($methodName, 0, strlen($methodName) - strlen($actionSuffix));

                $this->describe($task, $action);
            }
        }
    }

    protected function describe($task, $action)
    {
        if (Arr::has($this->scanned, $task . '.' . $action)) {
            return;
        }
        $this->scanned[$task . '.' . $action] = true;

        $delimiter = \Phalcon\Cli\Router\Route::getDelimiter();

        $actionSuffix = $this->dispatcher->getActionSuffix();

        $fullClass = 'App\Cli\Tasks\\' . Str::capitalize($task) . 'Task';

        $reflection = $this->getReflection($fullClass);

        $method = $reflection->getMethod($action . $actionSuffix);

        if ($action === 'main') {
            echo $task . PHP_EOL;
        } else {
            echo $task . $delimiter . $action . PHP_EOL;
        }
    }

    /**
     * @param string $class
     *
     * @return \ReflectionClass
     */
    protected function getReflection($class)
    {
        if (!Arr::has($this->reflections, $class)) {
            $this->reflections[$class] = new \ReflectionClass($class);
        }

        return $this->reflections[$class];
    }
}

<?php

namespace Luxury\Foundation\Cli;

use Luxury\Cli\Task;
use Luxury\Support\Arr;
use Luxury\Support\Str;
use Phalcon\Cli\Router\Route;

/**
 * Class ListTask
 *
 * @package     Luxury\Foundation\Cli
 */
class ListTask extends Task
{
    protected $reflections = [];
    protected $scanned     = [];
    protected $describes   = [];

    /**
     * List all command available.
     */
    public function mainAction()
    {
        $routes = $this->router->getRoutes();

        $delimiter = \Phalcon\Cli\Router\Route::getDelimiter();
        foreach ($routes as $route) {
            /** @var \Phalcon\Cli\Router\Route $route */
            // Default route
            if ($route->getPattern() === "#^(?:$delimiter)?([a-zA-Z0-9\\_\\-]+)[$delimiter]{0,1}$#" ||
                $route->getPattern() === "#^(?:$delimiter)?([a-zA-Z0-9\\_\\-]+)$delimiter([a-zA-Z0-9\\.\\_]+)($delimiter.*)*$#"
            ) {
                continue;
            }

            $this->describeRoute($route);
        }

        // $taskPath = $this->config->paths->app . 'Cli' . DIRECTORY_SEPARATOR . 'Tasks' . DIRECTORY_SEPARATOR;

       // $this->scanDir($taskPath);

        $this->table($this->describes);
    }

    protected function describeRoute(Route $route)
    {
        $pattern = $route->getPattern();

        $paths = $route->getPaths();

        $class = $paths['task'] . 'Task';

        $action = Arr::fetch($paths, 'action', 'main') . $this->dispatcher->getActionSuffix();

        $this->scanned[$class . '::' . $action] = true;

        $patternParams = '/' . preg_quote('([[:alnum:]]+)', '/') . '/';
        preg_match_all($patternParams, $pattern, $matches);

        foreach ($matches[0] as $k => $match) {
            $param = array_search($k + 1, $paths);
            if (!empty($param)) {
                $pattern = preg_replace($patternParams, ':' . $param . ':', $pattern, 1);
            }
        }

        $this->describe($pattern, $class, $action);
    }

    /*
    protected function scanDir($dir, $subnamespace = '')
    {
        $actionSuffix = $this->dispatcher->getActionSuffix();

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            if (is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
                continue;
            }
            if (!Str::endsWith($file, 'Task.php')) {
                continue;
            }

            $class     = str_replace('.php', '', $file);
            $fullClass = 'App\Cli\Tasks\\' . (!empty($subnamespace) ? $subnamespace . '\\' : '') . $class;

            $reflection = new \ReflectionClass($fullClass);

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $methodName = $method->getName();
                if (!Str::endsWith($methodName, $actionSuffix)) {
                    continue;
                }

                $task   = Str::lower(substr($class, 0, strlen($class) - 4));
                $action = substr($methodName, 0, strlen($methodName) - strlen($actionSuffix));

                $this->describeParsed($fullClass, $action);
            }
        }
    }

    protected function describeParsed($fullClass, $action)
    {
        if (Arr::has($this->scanned, $fullClass . '::' . $action)) {
            return;
        }
        $this->scanned[$fullClass . '::' . $action] = true;

        $delimiter = \Phalcon\Cli\Router\Route::getDelimiter();

        $actionSuffix = $this->dispatcher->getActionSuffix();

        $fullClass = 'App\Cli\Tasks\\' . Str::capitalize($task) . 'Task';

        $this->describe($task . ($action !== 'main' ? $delimiter . $action : ''), $fullClass, $action . $actionSuffix);
    }
*/
    protected function describe($pattern, $class, $action)
    {
        $reflection = $this->getReflection($class);

        try {
            $method = $reflection->getMethod($action);
        } catch (\Exception $e) {

        }
        $description = '';
        $params      = [];
        if (!empty($method)) {
            $docBlock = $method->getDocComment();

            preg_match_all('/\*\s*@(\w+)(.*)/', $docBlock, $annotations);
            $docBlock = preg_replace('/\*\s*@(\w+)(.*)/', '', $docBlock);


            preg_match_all('/\*([^\n\r]+)/', $docBlock, $lines);

            foreach ($lines[1] as $line) {
                $line = trim($line);
                if ($line == '*' || $line == '/') {
                    continue;
                }
                $description .= $line . ' ';
            }
        }

        $this->describes[] = [
            'cmd'  => $pattern,
            'desc' => $description
        ];
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

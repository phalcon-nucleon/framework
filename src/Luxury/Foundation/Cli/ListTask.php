<?php

namespace Luxury\Foundation\Cli;

use Luxury\Cli\Output\Group;
use Luxury\Cli\Task;
use Luxury\Support\Arr;
use Phalcon\Cli\Router\Route;

/**
 * Class ListTask
 *
 * @package     Luxury\Foundation\Cli
 */
class ListTask extends Task
{
    protected $reflections = [];
    protected $scanned = [];
    protected $describes = [];

    /**
     * List all command available.
     *
     * @description List all command available.
     */
    public function mainAction()
    {
        $routes = $this->router->getRoutes();

        $delimiter = Route::getDelimiter();
        foreach ($routes as $route) {
            /** @var Route $route */
            // Default route
            if ($route->getPattern() === "#^(?:$delimiter)?([a-zA-Z0-9\\_\\-]+)[$delimiter]{0,1}$#" ||
                $route->getPattern() === "#^(?:$delimiter)?([a-zA-Z0-9\\_\\-]+)$delimiter([a-zA-Z0-9\\.\\_]+)($delimiter.*)*$#"
            ) {
                continue;
            }

            $this->describeRoute($route);
        }

        $datas = [];

        foreach ($this->describes as $describe) {
            $datas[$describe['cmd']] = $describe['description'];
        }

        $this->line('Available Commands:');

        (new Group($this->output, $datas))->display();
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
                $pattern = preg_replace($patternParams, '<' . $param . '>', $pattern, 1);
            }
        }

        $this->describe($pattern, $class, $action);
    }

    protected function describe($pattern, $class, $action)
    {
        $infos = $this->getInfos($class, $action);

        if (!empty($infos['options'])) {
            $infos['options'] = implode(', ', $infos['options']);
        }
        if (!empty($infos['arguments'])) {
            $infos['arguments'] = implode(', ', $infos['arguments']);
        }

        $infos['cmd'] = $this->output->info($pattern);

        $this->describes[] = $infos;
    }

    /**
     * @param $class
     * @param $methodName
     *
     * @return array
     */
    protected function getInfos($class, $methodName)
    {
        $infos = [];
        $reflection = $this->getReflection($class);

        try {
            $method = $reflection->getMethod($methodName);
        } catch (\Exception $e) {

        }
        $description = '';
        if (!empty($method)) {
            $docBlock = $method->getDocComment();

            preg_match_all('/\*\s*@(\w+)(.*)/', $docBlock, $annotations);
            $docBlock = preg_replace('/\*\s*@(\w+)(.*)/', '', $docBlock);

            foreach ($annotations[1] as $k => $annotation) {
                switch ($annotation) {
                    case 'description':
                        $infos['description'] = trim($annotations[2][$k]);
                        break;
                    case 'argument':
                    case 'option':
                        $infos[$annotation . 's'][] = trim($annotations[2][$k]);
                        break;
                }
            }

            if (empty($infos['description'])) {
                preg_match_all('/\*([^\n\r]+)/', $docBlock, $lines);

                foreach ($lines[1] as $line) {
                    $line = trim($line);
                    if ($line == '*' || $line == '/') {
                        continue;
                    }
                    $description .= $line . ' ';
                }

                $infos['description'] = trim($description);
            }
        }

        return $infos;
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

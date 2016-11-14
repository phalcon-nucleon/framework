<?php

namespace Luxury\Foundation\Cli\Tasks;

use Luxury\Cli\Output\Decorate;
use Luxury\Cli\Output\Group;
use Luxury\Cli\Output\Helper;
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
    protected $scanned     = [];
    protected $describes   = [];

    /**
     * List all commands available.
     *
     * @description List all commands available.
     */
    public function mainAction()
    {
        $routes = $this->router->getRoutes();

        $delimiter = Route::getDelimiter();
        foreach ($routes as $route) {
            /** @var Route $route */
            // Default route
            $pattern = $route->getPattern();
            if ($pattern === "#^(?:$delimiter)?([a-zA-Z0-9\\_\\-]+)[$delimiter]{0,1}$#" ||
                $pattern === "#^(?:$delimiter)?([a-zA-Z0-9\\_\\-]+)$delimiter([a-zA-Z0-9\\.\\_]+)($delimiter.*)*$#"
            ) {
                continue;
            }

            $this->describeRoute($route);
        }

        $datas = [];

        foreach ($this->describes as $describe) {
            $datas[$describe['cmd']] = $describe['description'];
        }

        $this->line('Available Commands :');

        (new Group($this->output, $datas))->display();
    }

    protected function describeRoute(Route $route)
    {
        $paths = $route->getPaths();

        $class = $paths['task'];

        $action = Arr::fetch($paths, 'action', 'main') . $this->dispatcher->getActionSuffix();

        $this->scanned[$class . '::' . $action] = true;

        $compiled = Helper::describeRoutePattern($route);
        
        $this->describe($compiled, $class, $action);
    }

    protected function describe($pattern, $class, $action)
    {
        $infos = Helper::getTaskInfos($class, $action);

        if (!empty($infos['options'])) {
            $infos['options'] = implode(', ', $infos['options']);
        }
        if (!empty($infos['arguments'])) {
            $infos['arguments'] = implode(', ', $infos['arguments']);
        }

        $infos['cmd'] = Decorate::info($pattern);

        $this->describes[] = $infos;
    }
}

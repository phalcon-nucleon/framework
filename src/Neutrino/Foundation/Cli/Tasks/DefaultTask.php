<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Task;
use Phalcon\Cli\Router\Route;

/**
 * Class DefaultTask
 *
 * @package     Neutrino\Foundation\Cli\Tasks
 */
class DefaultTask extends Task
{

    public function mainAction()
    {
        $arguments = array_filter($this->application->getArguments());

        if (empty($arguments)) {
            $this->application->handle(['task' => ListTask::class]);
            return;
        }

        $lines[] = 'Command "' . implode(Route::getDelimiter(), $arguments) . '" not found.';

        foreach ($this->router->getRoutes() as $route) {
            $routes[] = explode(Route::getDelimiter(), $route->getPattern())[0];
        }

        if (!empty($routes) && !empty($alternatives = $this->findAlternatives($arguments[0], $routes))) {
            $lines[] = 'Did you mean ' . (count($alternatives) > 1 ? 'one of theses' : 'this') . '?';
            $lines   = array_merge($lines, array_map(function ($value) {
                return '  ' . $value;
            }, $alternatives));
        }

        $this->block($lines, 'error');
    }

    /**
     * (c) Fabien Potencier <fabien@symfony.com>
     *
     * @see https://github.com/symfony/console/blob/60d0efcb8470bf5cfbea84bff99cf1af0ccfdf00/Application.php#L921
     *
     * @param string $name
     * @param array  $collection
     *
     * @return array
     */
    protected function findAlternatives($name, array $collection)
    {
        $threshold    = 1e3;
        $alternatives = [];

        $collectionParts = [];
        foreach ($collection as $item) {
            $collectionParts[$item] = explode(':', $item);
        }

        foreach (explode(':', $name) as $i => $subname) {
            foreach ($collectionParts as $collectionName => $parts) {
                $exists = isset($alternatives[$collectionName]);
                if (!isset($parts[$i]) && $exists) {
                    $alternatives[$collectionName] += $threshold;
                    continue;
                } elseif (!isset($parts[$i])) {
                    continue;
                }

                $lev = levenshtein($subname, $parts[$i]);
                if ($lev <= strlen($subname) / 3 || '' !== $subname && false !== strpos($parts[$i], $subname)) {
                    $alternatives[$collectionName] = $exists ? $alternatives[$collectionName] + $lev : $lev;
                } elseif ($exists) {
                    $alternatives[$collectionName] += $threshold;
                }
            }
        }

        foreach ($collection as $item) {
            $lev = levenshtein($name, $item);
            if ($lev <= strlen($name) / 3 || false !== strpos($item, $name)) {
                $alternatives[$item] = isset($alternatives[$item]) ? $alternatives[$item] - $lev : $lev;
            }
        }

        $alternatives = array_filter($alternatives, function ($lev) use ($threshold) {
            return $lev < 2 * $threshold;
        });
        ksort($alternatives, SORT_NATURAL | SORT_FLAG_CASE);

        return array_keys($alternatives);
    }
}
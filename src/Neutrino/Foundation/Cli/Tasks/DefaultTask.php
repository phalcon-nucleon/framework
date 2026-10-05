<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Output\Helper;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Phalcon\Cli\Router\Route;
use Phalcon\Cli\Router\RouteInterface;

/**
 * Runs when no command matches: lists the commands, or suggests the closest ones.
 */
final class DefaultTask extends Task
{
    #[Description('List the commands, or suggest the closest ones to an unknown command.')]
    public function mainAction(): void
    {
        /** @var \Neutrino\Foundation\Cli\Kernel $application */
        $application = $this->getDI()->getShared(Services::APP);
        $arguments = array_values(array_filter(array_map(static fn(mixed $a): string => is_scalar($a) ? (string) $a : '', (array) $application->getArguments())));

        if ($arguments === []) {
            $application->handle(['task' => ListTask::class]);

            return;
        }

        $lines = ['Command "' . implode(Route::getDelimiter(), $arguments) . '" not found.'];

        /** @var \Neutrino\Cli\Router $router */
        $router = $this->getDI()->getShared(Services::ROUTER);

        $routes = [];
        /** @var RouteInterface $route */
        foreach ($router->getRoutes() as $route) {
            $routes[explode(Route::getDelimiter() ?: ' ', $route->getPattern())[0]] = $route;
        }

        $alternatives = self::findAlternatives($arguments[0], array_keys($routes));

        if ($alternatives !== []) {
            $lines[] = 'Did you mean ' . (count($alternatives) > 1 ? 'one of theses' : 'this') . ' ?';
            foreach ($alternatives as $alternative) {
                $lines[] = '  ' . Helper::describeRoutePattern($routes[$alternative]);
            }
        }

        $this->block($lines, 'error');
    }

    /**
     * Commands close to `$name` (levenshtein distance on each `:`-separated part), from Symfony Console.
     *
     * @param list<string> $collection
     *
     * @return list<string>
     */
    private static function findAlternatives(string $name, array $collection): array
    {
        $threshold = 1e3;
        $alternatives = [];

        $collectionParts = [];
        foreach ($collection as $item) {
            $collectionParts[$item] = explode(':', $item);
        }

        foreach (explode(':', $name) as $i => $subname) {
            foreach ($collectionParts as $collectionName => $parts) {
                $exists = isset($alternatives[$collectionName]);
                if (!isset($parts[$i])) {
                    if ($exists) {
                        $alternatives[$collectionName] += $threshold;
                    }
                    continue;
                }

                $lev = levenshtein($subname, $parts[$i]);
                if ($lev <= strlen($subname) / 3 || ($subname !== '' && str_contains($parts[$i], $subname))) {
                    $alternatives[$collectionName] = $exists ? $alternatives[$collectionName] + $lev : $lev;
                } elseif ($exists) {
                    $alternatives[$collectionName] += $threshold;
                }
            }
        }

        foreach ($collection as $item) {
            $lev = levenshtein($name, $item);
            if ($lev <= strlen($name) / 3 || str_contains($item, $name)) {
                $alternatives[$item] = isset($alternatives[$item]) ? $alternatives[$item] - $lev : $lev;
            }
        }

        $alternatives = array_filter($alternatives, static fn(float|int $lev): bool => $lev < 2 * $threshold);
        ksort($alternatives, SORT_NATURAL | SORT_FLAG_CASE);

        return array_map('strval', array_keys($alternatives));
    }
}

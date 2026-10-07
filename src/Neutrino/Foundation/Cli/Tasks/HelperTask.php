<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Output\Helper;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Phalcon\Cli\Router\RouteInterface;

/**
 * Help of a command: `help <command>`, or `<command> --help`.
 */
final class HelperTask extends Task
{
    #[Description('Display the help of a command.')]
    public function mainAction(): void
    {
        /** @var \Neutrino\Foundation\Cli\Kernel $application */
        $application = $this->getDI()->getShared(Services::APP);
        /** @var \Neutrino\Cli\Router $router */
        $router = $this->getDI()->getShared(Services::ROUTER);
        /** @var \Phalcon\Cli\Dispatcher $dispatcher */
        $dispatcher = $this->getDI()->getShared(Services::DISPATCHER);
        $suffix = $dispatcher->getActionSuffix();

        $application->displayNeutrinoVersion();

        // `<command> --help` gives the command line in `arguments`; `help <command>` leaves it in the kernel arguments.
        $arguments = $this->hasArg('arguments') ? $this->getArg('arguments') : $application->getArguments(true);

        // A task called directly (handle(['task' => …, 'action' => …, '--help'])).
        if (is_array($arguments) && isset($arguments['task']) && is_string($arguments['task'])) {
            $this->arguments['task'] = $arguments['task'];
            $this->arguments['action'] = $arguments['action'] ?? 'main';
            $arguments = '';
        }

        $command = self::command($arguments);

        if ($command === '' && !$this->hasArg('task')) {
            $task = self::class;
            $action = 'main' . $suffix;
        } elseif ($command !== '') {
            $router->handle($command);

            if ($router->wasMatched()) {
                $task = $router->getTaskName();
                $action = ($router->getActionName() ?: 'main') . $suffix;
            } else {
                $paths = $this->tryHandle($command);

                if ($paths === null) {
                    $this->block(["Command \"$command\" not found."], 'error');

                    return;
                }

                $task = self::string($paths['task'] ?? '');
                $action = (self::string($paths['action'] ?? null) ?: 'main') . $suffix;
            }
        } else {
            $task = self::string($this->getArg('task'));
            $action = (self::string($this->getArg('action')) ?: 'main') . $suffix;
        }

        $infos = Helper::getTaskInfos($task, $action);

        $route = $this->resolveRoute($task, $action);
        if ($route !== null) {
            $this->line('Usage :');
            $this->info("\t" . Helper::describeRoutePattern($route, true));
        }

        $this->line('Description :');
        $this->line("\t" . str_replace(PHP_EOL, PHP_EOL . "\t", $infos['description'] ?? $infos['__exception']));

        if (!empty($infos['arguments'])) {
            $this->line('Arguments :');
            foreach ($infos['arguments'] as $argument) {
                $this->line("\t" . $argument);
            }
        }
        if (!empty($infos['options'])) {
            $this->line('Options :');
            foreach ($infos['options'] as $option) {
                $this->line("\t" . $option);
            }
        }
    }

    private static function string(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * The command line the help is asked for: without the leading `help` command.
     */
    private static function command(mixed $arguments): string
    {
        $parts = is_array($arguments) ? array_values($arguments) : explode(' ', is_scalar($arguments) ? (string) $arguments : '');
        $parts = array_values(array_filter(
            array_map(static fn(mixed $p): string => is_scalar($p) ? trim((string) $p) : '', $parts),
            static fn(string $p): bool => $p !== '',
        ));

        if (($parts[0] ?? null) === 'help') {
            array_shift($parts);
        }

        return implode(' ', $parts);
    }

    private function resolveRoute(string $class, string $action): ?RouteInterface
    {
        /** @var \Neutrino\Cli\Router $router */
        $router = $this->getDI()->getShared(Services::ROUTER);
        /** @var \Phalcon\Cli\Dispatcher $dispatcher */
        $dispatcher = $this->getDI()->getShared(Services::DISPATCHER);

        /** @var RouteInterface $route */
        foreach ($router->getRoutes() as $route) {
            $paths = $route->getPaths();

            if (($paths['task'] ?? null) === $class && ($paths['action'] ?? 'main') . $dispatcher->getActionSuffix() === $action) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Finds the route of a command given with its arguments, by ignoring the parameters of the patterns.
     *
     * @return array<string, mixed>|null
     */
    private function tryHandle(string $command): ?array
    {
        /** @var \Neutrino\Cli\Router $router */
        $router = $this->getDI()->getShared(Services::ROUTER);

        /** @var RouteInterface $route */
        foreach ($router->getRoutes() as $route) {
            $pattern = $route->getCompiledPattern();

            do {
                $previous = $pattern;
                $pattern = (string) preg_replace('/\([^\(\)]*\)(?:[+*]|\{[\d,]\})?/', '', $pattern);
            } while ($pattern !== $previous);

            $pattern = trim(str_replace(' ', '\s*', $pattern));

            if (@preg_match($pattern, $command) === 1) {
                return $route->getPaths();
            }
        }

        return null;
    }
}

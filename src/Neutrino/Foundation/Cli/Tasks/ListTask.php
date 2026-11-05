<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Output\Group;
use Neutrino\Cli\Output\Helper;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Phalcon\Cli\Router\Route;
use Phalcon\Cli\Router\RouteInterface;

final class ListTask extends Task
{
    /** @var list<array<string, mixed>> */
    private array $describes = [];

    #[Description('List all commands available.')]
    public function mainAction(): void
    {
        $this->displayHeader();

        /** @var \Neutrino\Cli\Router $router */
        $router = $this->getDI()->getShared(Services::ROUTER);
        $delimiter = Route::getDelimiter();

        /** @var RouteInterface $route */
        foreach ($router->getRoutes() as $route) {
            // Default routes of the Phalcon CLI router
            $pattern = $route->getPattern();
            if ($pattern === "#^(?:$delimiter)?([a-zA-Z0-9\\_\\-]+)[$delimiter]{0,1}$#"
                || $pattern === "#^(?:$delimiter)?([a-zA-Z0-9\\_\\-]+)$delimiter([a-zA-Z0-9\\.\\_]+)($delimiter.*)*$#"
            ) {
                continue;
            }

            $this->describeRoute($route);
        }

        $datas = [];
        foreach ($this->describes as $describe) {
            $text = $describe['__exception'] ?? $describe['description'] ?? '';
            $line = explode(PHP_EOL, is_string($text) ? $text : '', 2)[0];
            $cmd = $describe['cmd'] ?? '';

            $datas[is_string($cmd) ? $cmd : ''] = isset($describe['__exception']) ? Decorate::error($line) : $line;
        }

        $this->notice('Available Commands :');

        (new Group($this->writer(), $datas, Group::SORT_ASC))->display();
    }

    private function describeRoute(RouteInterface $route): void
    {
        $paths = $route->getPaths();

        /** @var \Phalcon\Cli\Dispatcher $dispatcher */
        $dispatcher = $this->getDI()->getShared(Services::DISPATCHER);
        $action = (string) ($paths['action'] ?? 'main') . $dispatcher->getActionSuffix();

        $this->describe(Helper::describeRoutePattern($route, true), (string) ($paths['task'] ?? ''), $action);
    }

    private function describe(string $pattern, string $class, string $action): void
    {
        $infos = Helper::getTaskInfos($class, $action);

        $infos['cmd'] = isset($infos['__exception']) ? Decorate::error($pattern) : Decorate::info($pattern);

        $this->describes[] = $infos;
    }

    private function displayHeader(): void
    {
        /** @var \Neutrino\Foundation\Cli\Kernel $application */
        $application = $this->getDI()->getShared(Services::APP);
        $application->displayNeutrinoVersion();

        $this->notice('Usage :');
        $this->line('  command [options] [arguments]');
        $this->line('');
        $this->notice('Options :');
        $this->info('  -h, --help                     Display this help message');
        $this->info('  -q, --quiet                    Do not output any message');
        $this->info('  -s, --stats                    Display timing and memory usage information');
        $this->info('      --colors                   Force Colors output');
        $this->info('      --no-colors                Disable Colors output');
        $this->line('');
    }
}

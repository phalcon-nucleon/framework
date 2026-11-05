<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Task;
use Neutrino\Foundation\Http\RouteCompiler;
use Throwable;

final class RouteCacheTask extends Task
{
    #[Description('Cache the HTTP routes.')]
    public function mainAction(): void
    {
        $this->writer()->write(Decorate::notice(str_pad('Generating http-routes cache', 40)), false);

        try {
            RouteCompiler::write(HttpRoutes::load($this->getDI())->router, BASE_PATH);

            $this->info('Success');
        } catch (Throwable $e) {
            $this->error('Error');
            $this->block([$e->getMessage()], 'error');

            RouteCompiler::clear(BASE_PATH);
        }
    }
}

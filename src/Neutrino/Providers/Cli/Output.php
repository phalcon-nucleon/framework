<?php

declare(strict_types=1);

namespace Neutrino\Providers\Cli;

use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;
use Neutrino\Support\Provider;

/**
 * Console output, quiet when the kernel received `-q` / `--quiet`.
 */
class Output extends Provider
{
    protected string $name = Services\Cli::OUTPUT;

    protected bool $shared = true;

    protected function register(): Writer
    {
        $application = $this->getDI()->getShared(Services::APP);

        return new Writer(is_object($application) && method_exists($application, 'isQuiet') && $application->isQuiet());
    }
}

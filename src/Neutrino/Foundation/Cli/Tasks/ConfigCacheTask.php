<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Task;
use Neutrino\Config\ConfigCompiler;
use Throwable;

final class ConfigCacheTask extends Task
{
    #[Description('Cache the configuration.')]
    public function mainAction(): void
    {
        $this->writer()->write(Decorate::notice(str_pad('Generating configuration cache', 40)), false);

        try {
            ConfigCompiler::compile(BASE_PATH);

            $this->info('Success');
        } catch (Throwable $e) {
            $this->error('Error');
            $this->block([$e->getMessage()], 'error');
        }
    }
}

<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Task;
use Neutrino\Config\ConfigCompiler;

final class ConfigClearTask extends Task
{
    #[Description('Remove the configuration cache.')]
    public function mainAction(): void
    {
        ConfigCompiler::clear(BASE_PATH);

        $this->info('The configuration cache has been removed.');
    }
}

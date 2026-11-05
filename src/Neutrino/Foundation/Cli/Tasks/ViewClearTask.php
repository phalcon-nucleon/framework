<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Neutrino\Support\Path;
use Phalcon\Config\Config;

final class ViewClearTask extends Task
{
    #[Description('Remove the compiled views.')]
    public function mainAction(): void
    {
        /** @var Config $config */
        $config = $this->getDI()->getShared(Services::CONFIG);
        $compileDir = $config->path('view.compiled_path');

        if (is_string($compileDir) && $compileDir !== '') {
            $this->clear(Path::normalize($compileDir));
        }

        $this->info('Compiled views cleared!');
    }

    /**
     * Empties the directory (it is kept).
     */
    private function clear(string $directory): void
    {
        foreach (glob($directory . '/*') ?: [] as $path) {
            if (is_dir($path) && !is_link($path)) {
                $this->clear($path);
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
    }
}

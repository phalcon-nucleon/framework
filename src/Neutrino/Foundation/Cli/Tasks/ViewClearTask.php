<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Task;
use Neutrino\Support\Str;

/**
 * Class ViewClearTask
 *
 *  @package Neutrino\Foundation\Cli
 */
class ViewClearTask extends Task
{
    /**
     * Clear all compiled view files.
     *
     * @description Clear all compiled view files.
     */
    public function mainAction()
    {
        $compileDir = $this->config->view->compiled_path;

        $this->rm($compileDir);

        $this->info('Compiled views cleared!');
    }

    private function rm($path)
    {
        $path = Str::normalizePath($path);

        if (is_dir($path)) {
            foreach (glob($path . '/*') as $sub) {
                $this->rm($sub);
            }
        }

        @unlink($path);
    }
}

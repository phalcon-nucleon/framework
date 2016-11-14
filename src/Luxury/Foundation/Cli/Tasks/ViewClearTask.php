<?php

namespace Luxury\Foundation\Cli\Tasks;

use Luxury\Cli\Task;

/**
 * Class ViewClearTask
 *
 * @package Luxury\Foundation\Cli
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

        foreach (glob($compileDir . '*') as $file) {
            @unlink($file);
        }

        $this->info('Compiled views cleared!');
    }
}

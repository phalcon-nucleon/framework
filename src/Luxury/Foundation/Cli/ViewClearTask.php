<?php

namespace Luxury\Foundation\Cli;

use Luxury\Cli\Task;
use \RuntimeException;

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
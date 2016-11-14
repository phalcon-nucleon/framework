<?php

namespace Luxury\Foundation\Cli\Tasks;

use Luxury\Cli\Task;

/**
 * Class ClearCompiledTask
 *
 * @package Luxury\Foundation\Cli
 */
class ClearCompiledTask extends Task
{
    /**
     * @description Clear compilation.
     */
    public function mainAction()
    {
        $compileDir = $this->config->paths->base . 'bootstrap/compile/';

        if (file_exists($compileDir . 'loader.php')) {
            @unlink($compileDir . 'loader.php');
        }

        $this->info('The compiled loader has been removed.');
    }
}
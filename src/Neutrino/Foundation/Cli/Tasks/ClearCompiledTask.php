<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Task;

/**
 * Class ClearCompiledTask
 *
 *  @package Neutrino\Foundation\Cli
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
        if (file_exists($compileDir . 'compile.php')) {
            @unlink($compileDir . 'compile.php');
        }

        $this->info('The compiled loader has been removed.');
    }

    /**
     * Handle the post-[install|update] Composer event.
     *
     * @return void
     */
    public static function composerClearCompiled()
    {
        $compileDir = getcwd() . DIRECTORY_SEPARATOR . 'bootstrap/compile/';

        if (file_exists($compileDir . 'loader.php')) {
            @unlink($compileDir . 'loader.php');
        }
        if (file_exists($compileDir . 'compile.php')) {
            @unlink($compileDir . 'compile.php');
        }
    }
}

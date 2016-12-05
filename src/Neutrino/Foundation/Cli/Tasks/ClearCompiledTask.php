<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Task;
use Neutrino\Dotenv;

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
        $compileDir = Dotenv::env('BASE_PATH') . '/bootstrap/compile/';

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

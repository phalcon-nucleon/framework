<?php
/**
 * Created by PhpStorm.
 * User: xlzi590
 * Date: 03/11/2016
 * Time: 14:16
 */

namespace Luxury\Foundation\Cli;


use Luxury\Cli\Task;

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
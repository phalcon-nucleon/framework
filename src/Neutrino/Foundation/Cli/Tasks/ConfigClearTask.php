<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Task;

/**
 * Class ConfigClearTask
 *
 * @package Neutrino\Foundation\Cli\Tasks
 */
class ConfigClearTask extends Task
{
    /**
     * Clear configuration cache.
     *
     * @description Clear the configuration cache.
     *
     * @throws \Exception
     */
    public function mainAction()
    {
        if(file_exists($file = BASE_PATH . '/bootstrap/compile/config.php')){
            @unlink($file);
        }

        $this->info('The configuration cache has been removed.');
    }
}
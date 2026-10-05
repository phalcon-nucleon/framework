<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Task;
use Neutrino\Config\ConfigCompiler;

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
        ConfigCompiler::clear(BASE_PATH);

        $this->info('The configuration cache has been removed.');
    }
}
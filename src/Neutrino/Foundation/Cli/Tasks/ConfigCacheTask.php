<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Task;
use Neutrino\Config\ConfigCompiler;

/**
 * Class ConfigCacheTask
 *
 * @package     Neutrino\Foundation\Cli\Tasks
 */
class ConfigCacheTask extends Task
{

    /**
     * Configuration cache.
     *
     * @description Cache the configuration.
     *
     * @throws \Exception
     */
    public function mainAction()
    {
        $this->output->write(Decorate::notice(str_pad('Generating configuration cache', 40, ' ')), false);

        try {
            ConfigCompiler::compile(BASE_PATH);

            $this->info("Success");
        } catch (\Exception $e) {
            $this->error("Error");
            $this->block([$e->getMessage()], 'error');
        }
    }
}

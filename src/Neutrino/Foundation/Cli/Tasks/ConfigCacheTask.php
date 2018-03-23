<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Task;
use Neutrino\Config\ConfigPreloader;

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
        $this->info('Generating configuration cache');

        $preloader = new ConfigPreloader();

        try {
            $preloader->compile();
        } catch (\Exception $e) {
            $this->block([$e->getMessage()], 'error');
        }
    }
}

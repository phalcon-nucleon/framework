<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Task;

/**
 * Class ConfigCacheTask
 *
 * @package     Neutrino\Foundation\Cli\Tasks
 */
class ConfigCacheTask extends Task
{

    public function mainAction()
    {
        
    }

    protected function optimizeClass()
    {
        $preloader = (new Factory())->create(['skip' => true]);

        $handle = $preloader->prepareOutput($this->config->paths->base . 'bootstrap/compile/compile.php');

        $files = require __DIR__ . '/Optimize/compile.php';

        if (file_exists($this->config->paths->base . 'config/compile.php')) {
            $files = array_merge($files, require $this->config->paths->base . 'config/compile.php');
        }

        foreach ($files as $file) {
            try {
                fwrite($handle, $preloader->getCode($file, false) . PHP_EOL);
            } catch (\Exception $e) {
                //
            }
        }

        fclose($handle);
    }
}

<?php

namespace Neutrino\Foundation\Cli\Tasks;

use ClassPreloader\Factory;
use Neutrino\Cli\Task;
use Neutrino\Optimizer\Composer;

/**
 * Class OptimizeTask
 *
 *  @package Neutrino\Foundation\Cli
 */
class OptimizeTask extends Task
{
    /**
     * @var Composer
     */
    private $optimizer;

    /**
     * Optimize the loader.
     *
     * @description Optimize the loader.
     *
     * @option      -m, --memory: Optimize memory.
     */
    public function mainAction()
    {
        $this->optimizer = new Composer(
            $this->config->paths->base . 'bootstrap/compile/loader.php',
            $this->config->paths->vendor . 'composer',
            $this->config->paths->base
        );

        if ($this->hasOption('m', 'memory')) {
            $res = $this->optimizeMemory();
        } else {
            $res = $this->optimizeProcess();
        }
        if ($res === false) {
            $this->error('Autoloader generation has failed.');
        }
        $this->optimizeClass();
    }

    /**
     * Build an memory optimized autoloader
     *
     * @return bool|int
     */
    protected function optimizeMemory()
    {
        $this->info('Generating memory optimized auto-loader');

        return $this->optimizer->optimizeMemory();
    }

    /**
     * Build an process optimized autoloader
     *
     * @return bool|int
     */
    protected function optimizeProcess()
    {
        $this->info('Generating optimized auto-loader');

        return $this->optimizer->optimizeProcess();
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

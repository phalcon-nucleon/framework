<?php

namespace Neutrino\Foundation\Cli\Tasks;

use ClassPreloader\Factory;
use Neutrino\Cli\Task;
use Neutrino\Optimizer\Composer;

/**
 * Class OptimizeTask
 *
 * @property-read \Neutrino\Opcache\Manager $opcache
 *
 * @package Neutrino\Foundation\Cli
 */
class OptimizeTask extends Task
{
    /**
     * @var Composer
     */
    private $optimizer;

    private $compileTasks = [
        ConfigCacheTask::class,
        DotconstCacheTask::class
    ];

    /**
     * Optimize the autoloader.
     *
     * @description Optimize the autoloader.
     *
     * @option      -m, --memory: Optimize memory.
     */
    public function mainAction()
    {
        $this->optimizer = new Composer(
            BASE_PATH . '/bootstrap/compile/loader.php',
            BASE_PATH . '/vendor/composer',
            BASE_PATH
        );

        if ($this->hasOption('m', 'memory')) {
            $res = $this->optimizeMemory();
        } else {
            $res = $this->optimizeProcess();
        }
        if ($res === false) {
            $this->error('Autoloader generation has failed');
        }

        $this->info('Compiling common classes');

        $this->optimizeClass();

        if(!$this->hasOption('withoutOpache')) {
            $this->info('Try opcache force invalidate files generated');
            try {
                $res = true;
                foreach ([
                             BASE_PATH . '/bootstrap/compile/loader.php',
                             BASE_PATH . '/bootstrap/compile/compile.php'
                         ] as $file) {
                    $res &= $this->opcache->invalidate($file, true);
                }
            } catch (\Exception $e) {
                $res = false;
            }

            $this->info($res ? 'Opcache invalidate with success' : 'Opcache invalidate fail');
        }

        foreach ($this->compileTasks as $compileTask) {
            $compileTask = new $compileTask;

            $compileTask->mainAction();
        }
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

        $handle = $preloader->prepareOutput(BASE_PATH . '/bootstrap/compile/compile.php');

        $files = require __DIR__ . '/Optimize/compile.php';

        if (file_exists(BASE_PATH . '/config/compile.php')) {
            $files = array_merge($files, require BASE_PATH . '/config/compile.php');
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

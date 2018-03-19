<?php

namespace Neutrino\Foundation\Cli\Tasks;

use ClassPreloader\Factory;
use Neutrino\Cli\Task;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Neutrino\Optimizer\Composer;
use Neutrino\Support\Str;

/**
 * Class OptimizeTask
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
     * @option      -f, --force: Force optimization.
     */
    public function mainAction()
    {
        if(APP_DEBUG && !$this->hasOption('f', 'force')){
            $this->info('Application is in debug mode.');
            $this->info('For optimize in debug please use the --force, -f option.');

            return;
        }

        $this->optimizer = $this->getDI()->get(Composer::class, [
            BASE_PATH . '/bootstrap/compile/loader.php',
            BASE_PATH . '/vendor/composer',
            BASE_PATH
        ]);

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

        foreach ($this->compileTasks as $compileTask) {
            $this->application->handle([
                'task' => $compileTask
            ]);
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
            $files = array_unique(array_map(function ($path) {
                return Str::normalizePath($path);
            }, array_merge($files, require BASE_PATH . '/config/compile.php')));
        }

        foreach ($files as $file) {
            try {
                fwrite($handle, $preloader->getCode(Str::normalizePath($file), false) . PHP_EOL);
            } catch (\Exception $e) {
                $this->block(array_merge([
                    "File : " . Str::normalizePath($file),
                ], explode("\n", Helper::format(Error::fromException($e)))), 'warn', 4);
            }
        }

        fclose($handle);
    }
}

<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Task;
use Neutrino\Optimizer\Composer;
use Neutrino\PhpPreloader\Exceptions\DirConstantException;
use Neutrino\PhpPreloader\Exceptions\FileConstantException;
use Neutrino\PhpPreloader\Factory;
use Neutrino\Support\Path;

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
        $outputFile = BASE_PATH . '/bootstrap/compile/compile.php';
        $compileConfigFile = BASE_PATH . '/config/compile.php';

        $preloader = (new Factory())->create();

        $handle = $preloader->prepareOutput($outputFile);

        $files = require __DIR__ . '/Optimize/compile.php';

        if (file_exists($compileConfigFile)) {
            $files = array_unique(array_map(function ($path) {
                return Path::normalize($path);
            }, array_merge($files, require $compileConfigFile)));
        }

        try {
            $parts = [];

            foreach ($files as $file) {
                $file = Path::normalize($file);

                try {
                    $stmts = $preloader->parse($file);
                    $stmts = $preloader->traverse($stmts);

                    $parts = array_merge($parts, $stmts);
                } catch (DirConstantException $e) {
                    $this->block([
                        "Usage of __DIR__ constant is prohibited. Use BASE_PATH . '/path' instead.",
                        "in : $file"
                    ], 'error');
                } catch (FileConstantException $e) {
                    $this->block([
                        "Usage of __FILE__ constant is prohibited. Use BASE_PATH . '/path' instead.",
                        "in : $file"
                    ], 'error');
                } catch (\Exception $e) {
                    $this->block([
                        $e->getMessage(),
                        "in : $file"
                    ], 'error');
                }
            }

            fwrite($handle, $preloader->prettyPrint($parts) . PHP_EOL);
        } finally {
            if (isset($r) && is_resource($r)) {
                fclose($r);
            }
            if (isset($e)) {
                @unlink($outputFile);
            }
        }
    }
}

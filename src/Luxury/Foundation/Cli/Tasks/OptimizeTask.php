<?php

namespace Luxury\Foundation\Cli\Tasks;

use Luxury\Cli\Task;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;

/**
 * Class OptimizeTask
 *
 * @package Luxury\Foundation\Cli
 */
class OptimizeTask extends Task
{
    /**
     * Optimize the loader.
     *
     * @description Optimize the loader.
     *
     * @option      -m, --memory: Optimize memory.
     */
    public function mainAction()
    {
        if ($this->hasOption('m', 'memory')) {
            $this->info('Generating memory optimized auto-loader');
            $this->optimizeMemory();
        } else {
            $this->info('Generating optimized auto-loader');
            $this->optimizeProcess();
        }
    }

    /**
     *
     */
    protected function optimizeMemory()
    {
        $this->callComposer();

        $files = $this->getAutoload('files');
        $namespaces = $this->getAutoload('namespaces');
        $psr = $this->getAutoload('psr4');

        $_namespaces = [];
        $_dirs = [];
        foreach ($namespaces as $namespace => $directories) {
            $_namespaces[trim($namespace, '\\')] = $directories;
            foreach ($directories as $directory) {
                $_dirs[$directory] = $directory;
            }
        }
        foreach ($psr as $namespace => $dir) {
            $_namespaces[trim($namespace, '\\')] = $dir;
        }

        $this->generateOutput($files, $_namespaces, $_dirs);
    }

    protected function optimizeProcess()
    {
        $this->callComposer(true);

        $files = $this->getAutoload('files');
        $classes = $this->getAutoload('classmap');

        $this->generateOutput($files, null, null, $classes);
    }

    /**
     *
     *
     * @param string $file
     *
     * @return array|mixed
     */
    protected function getAutoload($file)
    {
        $path = $this->config->paths->vendor . 'composer/autoload_' . $file . '.php';

        if (file_exists($path)) {
            return require $path;
        }

        return [];
    }

    /**
     * Return the composer command line
     *
     * @return string
     */
    protected function getComposerCmd()
    {
        if (!file_exists($this->config->paths->base . 'composer.phar')) {
            return 'composer';
        }

        $binary = ProcessUtils::escapeArgument((new PhpExecutableFinder())->find(false));

        return $binary . ' composer.phar';
    }

    /**
     * Call composer
     *
     * @param bool $optimize Optimize
     */
    protected function callComposer($optimize = false)
    {
        $process = (new Process('', $this->config->paths->base))->setTimeout(null);

        $cmd = trim($this->getComposerCmd() . ' dump-autoload ' . ($optimize ? '--optimize' : ''));

        $process->setCommandLine($cmd);

        $process->run();
    }

    /**
     * Generate loader
     *
     * @param array|null $files
     * @param array|null $namespaces
     * @param array|null $directories
     * @param array|null $classmap
     */
    protected function generateOutput($files = null, $namespaces = null, $directories = null, $classmap = null)
    {
        $output = '<?php' . PHP_EOL . '$loader = new Phalcon\Loader;' . PHP_EOL;

        if (!empty($files)) {
            $output .= '$loader->registerFiles(' . var_export(array_values($files), true) . ');' . PHP_EOL;
        }
        if (!empty($directories)) {
            $output .= '$loader->registerDirs(' . var_export(array_values($directories), true) . ');' . PHP_EOL;
        }
        if (!empty($namespaces)) {
            $output .= '$loader->registerNamespaces(' . var_export($namespaces, true) . ');' . PHP_EOL;
        }
        if (!empty($classmap)) {
            $output .= '$loader->registerClasses(' . var_export($classmap, true) . ');' . PHP_EOL;
        }

        $output .= '$loader->register();' . PHP_EOL;

        file_put_contents($this->config->paths->base . 'bootstrap/compile/loader.php', $output);
    }
}

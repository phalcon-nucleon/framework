<?php

namespace Neutrino\Foundation\Cli\Tasks;

use ClassPreloader\Factory;
use Neutrino\Cli\Task;

/**
 * Class OptimizeTask
 *
 *  @package Neutrino\Foundation\Cli
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
        $this->callComposer();

        $this->info('Generating memory optimized auto-loader');

        $files      = $this->getAutoload('files');
        $namespaces = $this->getAutoload('namespaces');
        $psr        = $this->getAutoload('psr4');
        $classes    = $this->getAutoload('classmap');

        $_namespaces = [];
        $_dirs       = [];
        foreach ($namespaces as $namespace => $directories) {
            $_namespaces[trim($namespace, '\\')] = $directories;
            foreach ($directories as $directory) {
                $_dirs[$directory] = $directory;
            }
        }
        foreach ($psr as $namespace => $dir) {
            $_namespaces[trim($namespace, '\\')] = $dir;
        }

        return $this->generateOutput($files, $_namespaces, $_dirs, $classes);
    }

    /**
     * Build an process optimized autoloader
     *
     * @return bool|int
     */
    protected function optimizeProcess()
    {
        $this->callComposer(true);

        $this->info('Generating optimized auto-loader');

        $files   = $this->getAutoload('files');
        $classes = $this->getAutoload('classmap');

        return $this->generateOutput($files, null, null, $classes);
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

        $binary = getenv('PHP_BINARY') ?: PHP_BINARY;

        return $binary . ' composer.phar';
    }

    /**
     * Call composer
     *
     * @param bool $optimize Optimize
     */
    protected function callComposer($optimize = false)
    {
        $cmd = trim($this->getComposerCmd() . ' dump-autoload ' . ($optimize ? '--optimize' : ''));

        if (DIRECTORY_SEPARATOR === '\\') {
            $cmd = 'cmd /Q /C "' . $cmd . '" > NUL 2> NUL';
        } else {
            $cmd = $cmd . ' 1> /dev/null 2> /dev/null';
        }

        $this->info('Composer dump-autoload');

        $resource = proc_open($cmd, [], $pipes, $this->config->paths->base);

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        proc_close($resource);
    }

    /**
     * Generate loader
     *
     * @param array|null $files
     * @param array|null $namespaces
     * @param array|null $directories
     * @param array|null $classmap
     *
     * @return int|bool
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

        return file_put_contents($this->config->paths->base . 'bootstrap/compile/loader.php', $output);
    }
}

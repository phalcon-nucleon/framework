<?php

namespace Neutrino\Optimizer;

use Phalcon\Di\Injectable;

/**
 * Class Composer
 *
 * @package Neutrino\Optimizer
 */
class Composer extends Injectable
{
    /**
     * @var string
     */
    private $loaderFilePath;

    /**
     * @var \Neutrino\Optimizer\Composer\Autoload
     */
    private $autoload;

    /**
     * @var \Neutrino\Optimizer\Composer\Script
     */
    private $composer;

    /**
     * Composer constructor.
     *
     * @param string      $loaderFilePath
     * @param string      $composerPath
     * @param string|null $basePath The path where composer will may get called
     */
    public function __construct($loaderFilePath, $composerPath, $basePath = null)
    {
        $this->loaderFilePath = $loaderFilePath;

        $di = $this->getDI();
        $this->autoload = $di->get(Composer\Autoload::class, [$composerPath]);
        $this->composer = $di->get(Composer\Script::class, [$basePath]);
    }

    /**
     * Build an memory optimized autoloader
     *
     * @return bool|int
     */
    public function optimizeMemory()
    {
        $this->composer->dumpautoload(false);

        $files      = $this->autoload->getFiles();
        $namespaces = $this->autoload->getNamespaces();
        $psr        = $this->autoload->getPsr4();
        $classes    = $this->autoload->getClassmap();

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
    public function optimizeProcess()
    {
        $this->composer->dumpautoload(true);

        $files   = $this->autoload->getFiles();
        $classes = $this->autoload->getClassmap();

        return $this->generateOutput($files, null, null, $classes);
    }

    /**
     * Generate phalcon loader
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
        $res = fopen($this->loaderFilePath, 'w');

        if ($res == false) {
            return false;
        }

        fwrite($res, '<?php' . PHP_EOL . '$loader = new Phalcon\Loader;' . PHP_EOL);

        if (!empty($files)) {
            fwrite($res, '$loader->registerFiles(' . var_export(array_values($files), true) . ');' . PHP_EOL);
        }
        if (!empty($directories)) {
            fwrite($res, '$loader->registerDirs(' . var_export(array_values($directories), true) . ');' . PHP_EOL);
        }
        if (!empty($namespaces)) {
            fwrite($res, '$loader->registerNamespaces(' . var_export($namespaces, true) . ');' . PHP_EOL);
        }
        if (!empty($classmap)) {
            fwrite($res, '$loader->registerClasses(' . var_export($classmap, true) . ');' . PHP_EOL);
        }

        fwrite($res, '$loader->register();' . PHP_EOL);

        fclose($res);

        return true;
    }
}

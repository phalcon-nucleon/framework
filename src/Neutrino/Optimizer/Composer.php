<?php

namespace Neutrino\Optimizer;

use Neutrino\Support\Arr;
use Neutrino\Support\Path;
use Phalcon\Di\Injectable;
use Phalcon\Version;

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
     * @var string
     */
    private $basePath;

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
        $this->basePath = $basePath;

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

        fwrite($res, '<?php' . "\n");

        if (isset($this->basePath)) {
            $relativePath = Path::findRelative(dirname($this->loaderFilePath), $this->basePath);
            fwrite($res, '$basePath = __DIR__ . ' . var_export('/' . $relativePath . '/', true) . ";\n");
        }

        fwrite($res, '$loader = new Phalcon\Loader;' . "\n");

        if (Version::getPart(Version::VERSION_MAJOR) >= 3 && Version::getPart(Version::VERSION_MEDIUM) >= 4) {
            fwrite($res, '$loader->setFileCheckingCallback("stream_resolve_include_path");' . "\n");
        }

        if (!empty($files)) {
            fwrite($res, '$loader->registerFiles(' . $this->prepareOutput(array_values($files)) . ');' . "\n");
        }
        if (!empty($directories)) {
            fwrite($res, '$loader->registerDirs(' . $this->prepareOutput(array_values($directories)) . ');' . "\n");
        }
        if (!empty($namespaces)) {
            fwrite($res, '$loader->registerNamespaces(' . $this->prepareOutput($namespaces) . ');' . "\n");
        }
        if (!empty($classmap)) {
            fwrite($res, '$loader->registerClasses(' . $this->prepareOutput($classmap) . ');' . "\n");
        }

        fwrite($res, '$loader->register();' . "\n");

        fclose($res);

        return true;
    }

    protected function prepareOutput(array $items)
    {
        $items = Arr::map(function ($item) {
            return str_replace(DIRECTORY_SEPARATOR, '/', $item);
        }, $items, true);

        $output = var_export($items, true);

        if (isset($this->basePath)) {
            $bs = substr(var_export(str_replace(DIRECTORY_SEPARATOR, '/', $this->basePath) . '/', true), 1, -1);

            $output = preg_replace("/'" . preg_quote($bs, '/') . '/', "\$basePath . '", $output);
        }

        return $output;
    }
}

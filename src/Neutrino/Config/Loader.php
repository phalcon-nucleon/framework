<?php

namespace Neutrino\Config;

use Phalcon\Config;

/**
 * Class Loader
 *
 * @package     Neutrino\Config
 */
class Loader
{
    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $compilePath;

    /**
     * @var array
     */
    private $excludes;

    /**
     * Loader constructor.
     *
     * @param        $basePath
     * @param string $compilePath
     * @param array  $excludes
     */
    public function __construct($basePath, $compilePath = '/bootstrap/compile', array $excludes = [])
    {
        $this->setBasePath($basePath)
            ->setCompilePath($compilePath)
            ->setExcludes($excludes);
    }

    /**
     * @return \Phalcon\Config
     */
    public function load()
    {
        if (!is_null($config = $this->loadFromCompile())) {
            return $config;
        } else {
            return $this->loadFromFiles();
        }
    }

    /**
     * @return \Phalcon\Config
     */
    public function loadFromFiles()
    {
        $config = [];

        foreach (glob($this->basePath . '/config/*.php') as $file) {
            if (!isset($this->excludes[$fileName = basename($file, '.php')])) {
                $config[$fileName] = require $file;
            }
        }

        return new Config($config);
    }

    /**
     * @return null|\Phalcon\Config
     */
    public function loadFromCompile()
    {
        if (file_exists($compilePath = $this->basePath . $this->compilePath . '/config.php')) {
            return new Config(require $compilePath);
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param mixed $basePath
     *
     * @return Loader
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * @return string
     */
    public function getCompilePath()
    {
        return $this->compilePath;
    }

    /**
     * @param string $compilePath
     *
     * @return Loader
     */
    public function setCompilePath($compilePath)
    {
        $this->compilePath = $compilePath;

        return $this;
    }

    /**
     * @return array
     */
    public function getExcludes()
    {
        return $this->excludes;
    }

    /**
     * @param array $excludes
     *
     * @return Loader
     */
    public function setExcludes(array $excludes)
    {
        $this->excludes = empty($excludes) ? $excludes : array_flip($excludes);

        return $this;
    }
}

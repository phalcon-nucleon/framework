<?php

namespace Neutrino\Test;

use Mockery;
use Neutrino\Foundation\Bootstrap;
use Neutrino\Support\Facades\Facade;
use Phalcon\Config;
use Phalcon\Config as PhConfig;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\DiInterface;
use PHPUnit_Framework_TestCase as UnitTestCase;

/**
 * Class UnitTestCase
 *
 * @package Phalcon\Test
 */
abstract class TestCase extends UnitTestCase implements InjectionAwareInterface
{
    /**
     * Holds the configuration variables and other stuff
     * I can use the DI container but for tests like the Translate
     * we do not need the overhead
     *
     * @var Config|null
     */
    protected static $config;

    /**
     * @var \Phalcon\Application|\Phalcon\Cli\Console|\Neutrino\Foundation\Kernelize
     */
    protected $app;

    /**
     * @var \Neutrino\Foundation\Bootstrap
     */
    protected $bootstrap;

    /**
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->checkExtension('phalcon');

        // Creating the application
        $this->bootstrap = new Bootstrap(self::getConfig());
        $this->app = $this->bootstrap->make($this->kernel());
        $this->app->boot();
    }

    /**
     * @return string
     */
    protected function kernel()
    {
        return static::kernelClassInstance();
    }

    /**
     * @return string
     */
    protected static function kernelClassInstance()
    {
        throw new \RuntimeException("kernelClassInstance not implemented.");
    }

    protected function tearDown()
    {
        Mockery::close();
        Facade::clearResolvedInstances();

        ob_start();
        $this->app->terminate();
        ob_end_clean();
        $this->app->getDI()->reset();
        $this->app = null;

        parent::tearDown();
    }

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass()
    {
        self::$config = new PhConfig();

        parent::setUpBeforeClass();
    }

    /**
     * Checks if a particular extension is loaded and if not it marks
     * the tests skipped
     *
     * @param mixed $extension
     */
    public function checkExtension($extension)
    {
        $message = function ($ext) {
            sprintf('Warning: %s extension is not loaded', $ext);
        };

        if (is_array($extension)) {
            foreach ($extension as $ext) {
                if (!extension_loaded($ext)) {
                    $this->markTestSkipped($message($ext));
                    break;
                }
            }
        } elseif (!extension_loaded($extension)) {
            $this->markTestSkipped($message($extension));
        }
    }

    /**
     * Returns a unique file name
     *
     * @param  string $prefix A prefix for the file
     * @param  string $suffix A suffix for the file
     *
     * @return string
     */
    protected function getFileName($prefix = '', $suffix = 'log')
    {
        $prefix = ($prefix) ? $prefix . '_' : '';
        $suffix = ($suffix) ? $suffix : 'log';

        return uniqid($prefix, true) . '.' . $suffix;
    }

    /**
     * Removes a file from the system
     *
     * @param string $path
     * @param string $fileName
     */
    protected function cleanFile($path, $fileName)
    {
        $file = (substr($path, -1, 1) != "/") ? ($path . '/') : $path;
        $file .= $fileName;

        $actual = file_exists($file);

        if ($actual) {
            unlink($file);
        }
    }

    /**
     * Sets the Config object.
     *
     * @param Config|array $config
     * @param bool         $merge
     */
    public static function setConfig($config, $merge = true)
    {
        if (is_array($config)) {
            $config = new PhConfig($config);
        }

        if (isset(self::$config) && $merge) {
            self::$config->merge($config);

            return;
        }

        self::$config = $config;
    }

    /**
     * Returns the Config object if any.
     *
     * @return null|Config
     */
    public static function getConfig()
    {
        if(is_array(self::$config)){
            return new PhConfig(self::$config);
        }
        if(self::$config instanceof Config){
            return self::$config;
        }

        return new PhConfig();
    }

    /**
     * Sets the Dependency Injector.
     *
     * @param  DiInterface $di
     *
     * @return $this
     */
    public function setDI(DiInterface $di)
    {
        return $this->app->setDI($di);
    }

    /**
     * Returns the internal Dependency Injector.
     *
     * @return DiInterface
     */
    public function getDI()
    {
        return $this->app->getDI();
    }
}

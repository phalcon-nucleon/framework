<?php

declare(strict_types=1);

namespace Neutrino\Test;

use Mockery;
use Neutrino\Config\Config;
use Neutrino\Foundation\Bootstrap;
use Neutrino\Interfaces\Kernelable;
use Neutrino\Support\Facades\Facade;
use Phalcon\Config\Config as PhalconConfig;
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Support\Version;
use PHPUnit\Framework\TestCase as UnitTestCase;
use RuntimeException;

/**
 * Boots the application kernel before each test, and terminates it after.
 *
 * The kernel is given by {@see TestCase::kernelClassInstance()}, the configuration by {@see TestCase::setConfig()}.
 */
abstract class TestCase extends UnitTestCase implements InjectionAwareInterface
{
    /**
     * Configuration of the kernel. Reset before each test class.
     */
    protected static ?PhalconConfig $config = null;

    /**
     * The booted kernel.
     */
    protected (Kernelable&InjectionAwareInterface)|null $app = null;

    protected ?Bootstrap $bootstrap = null;

    protected function setUp(): void
    {
        parent::setUp();

        self::assertPhalconIsAvailable();

        $this->bootstrap = new Bootstrap(self::getConfig());
        $this->app = $this->bootstrap->make($this->kernel());
        $this->app->boot();
    }

    protected function tearDown(): void
    {
        // Only when loaded: no Mockery means no mock to verify.
        if (class_exists(Mockery::class, false)) {
            Mockery::close();
        }

        Facade::clearResolvedInstances();

        if ($this->app !== null) {
            ob_start();
            try {
                $this->app->terminate();
            } finally {
                ob_end_clean();
            }

            $this->app = null;
        }

        $this->bootstrap = null;

        Di::reset();

        parent::tearDown();
    }

    public static function setUpBeforeClass(): void
    {
        self::$config = new Config();

        parent::setUpBeforeClass();
    }

    /**
     * Class of the kernel to boot.
     *
     * @return class-string<Kernelable&InjectionAwareInterface>
     */
    protected function kernel(): string
    {
        return static::kernelClassInstance();
    }

    /**
     * @return class-string<Kernelable&InjectionAwareInterface>
     */
    protected static function kernelClassInstance(): string
    {
        throw new RuntimeException(static::class . '::kernelClassInstance() not implemented.');
    }

    /**
     * Fails when Phalcon is not available (extension, or the phalcon/phalcon package).
     *
     * Tests are not skipped: a run without Phalcon would otherwise look green.
     */
    public static function assertPhalconIsAvailable(): void
    {
        if (!class_exists(Version::class)) {
            self::fail('Phalcon is not available: install the phalcon extension (or phalcon/phalcon).');
        }
    }

    /**
     * Marks the test skipped when one of the extensions is not loaded.
     *
     * @param string|list<string> $extension
     */
    public function checkExtension(string|array $extension): void
    {
        foreach ((array) $extension as $ext) {
            if (!extension_loaded($ext)) {
                $this->markTestSkipped(sprintf('Warning: %s extension is not loaded', $ext));
            }
        }
    }

    /**
     * Returns a unique file name.
     */
    protected function getFileName(string $prefix = '', string $suffix = 'log'): string
    {
        $prefix = $prefix !== '' ? $prefix . '_' : '';
        $suffix = $suffix !== '' ? $suffix : 'log';

        return uniqid($prefix, true) . '.' . $suffix;
    }

    /**
     * Removes a file if it exists.
     */
    protected function cleanFile(string $path, string $fileName): void
    {
        $file = rtrim($path, '/') . '/' . $fileName;

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Sets the configuration of the kernel, merged with the current one by default.
     *
     * @param PhalconConfig|array<string, mixed> $config
     */
    public static function setConfig(PhalconConfig|array $config, bool $merge = true): void
    {
        if (is_array($config)) {
            $config = new Config($config);
        }

        if (self::$config !== null && $merge) {
            self::$config->merge($config);

            return;
        }

        self::$config = $config;
    }

    public static function getConfig(): PhalconConfig
    {
        return self::$config ??= new Config();
    }

    public function setDI(DiInterface $container): void
    {
        $this->kernelInstance()->setDI($container);
    }

    public function getDI(): DiInterface
    {
        return $this->kernelInstance()->getDI();
    }

    /**
     * @return Kernelable&InjectionAwareInterface
     */
    protected function kernelInstance(): Kernelable
    {
        return $this->app ?? throw new RuntimeException('The kernel is not booted.');
    }
}

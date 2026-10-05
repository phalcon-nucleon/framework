<?php

declare(strict_types=1);

namespace Test\TestCase;

use Neutrino\Test\FuncTestCase;

/**
 * Base class of the framework tests that need a booted kernel (the fake app's HTTP kernel by default).
 *
 * `self::$cache_dir` is a temporary directory unique to the process, emptied after each test.
 */
abstract class TestCase extends FuncTestCase
{
    use TraitTestCase {
        setUpBeforeClass as private setUpKernelConfig;
    }

    public static string $cache_dir = '';

    public static function setUpBeforeClass(): void
    {
        self::$cache_dir = self::dataDirectory();

        self::setUpKernelConfig();
    }

    protected function setUp(): void
    {
        if (!is_dir(self::$cache_dir) && !mkdir(self::$cache_dir, 0777, true) && !is_dir(self::$cache_dir)) {
            throw new \RuntimeException('Cannot create ' . self::$cache_dir);
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        self::removeDirectory(self::$cache_dir);
    }

    /**
     * Temporary directory of the process: suites can run in parallel.
     */
    public static function dataDirectory(): string
    {
        return sys_get_temp_dir() . '/nucleon-tests-' . getmypid() . '/';
    }

    private static function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (array_diff((array) scandir($dir), ['.', '..']) as $item) {
            $path = rtrim($dir, '/') . '/' . $item;
            is_dir($path) && !is_link($path) ? self::removeDirectory($path) : @unlink($path);
        }

        @rmdir($dir);
    }
}

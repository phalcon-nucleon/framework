<?php

namespace Test\Optimizer;

use Neutrino\Optimizer\Composer;
use Test\TestCase\TestCase;

/**
 * Class ComposerTest
 *
 * @package Test
 */
class ComposerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        if (!is_dir(__DIR__ . '/.data')) {
            mkdir(__DIR__ . '/.data');
        }
    }

    public function tearDown()
    {
        parent::tearDown();

        foreach (glob(__DIR__ . '/.data/*') as $file) {
            @unlink($file);
        }
        rmdir(__DIR__ . '/.data');
    }

    public function dataGenerateOutput()
    {
        return [
            [
                'files'       => ['123' => str_replace(DIRECTORY_SEPARATOR, '/', __FILE__)],
                'namespaces'  => ['namespaces' => [str_replace(DIRECTORY_SEPARATOR, '/', __DIR__)]],
                'directories' => [str_replace(DIRECTORY_SEPARATOR, '/', __DIR__)],
                'classmap'    => ['Class_A' => str_replace(DIRECTORY_SEPARATOR, '/', __FILE__)],
            ]
        ];
    }

    /**
     * @dataProvider dataGenerateOutput
     */
    public function testGenerateOutput($files, $namespaces, $directories, $classmap)
    {
        $composer = new Composer(__DIR__ . '/.data/loader.php', null, null);

        $reflection = new \ReflectionClass(Composer::class);

        $method = $reflection->getMethod('generateOutput');

        $method->setAccessible(true);

        $this->assertTrue($method->invoke($composer, $files, $namespaces, $directories, $classmap));

        $this->assertTrue(file_exists(__DIR__ . '/.data/loader.php'));

        $content = file_get_contents(__DIR__ . '/.data/loader.php');

        $cmd = [
            '<?php',
            '$loader = new Phalcon\Loader;',
            '$loader->registerFiles(' . var_export(array_values($files), true) . ');',
            '$loader->registerDirs(' . var_export(array_values($directories), true) . ');',
            '$loader->registerNamespaces(' . var_export($namespaces, true) . ');',
            '$loader->registerClasses(' . var_export($classmap, true) . ');',
            '$loader->register();'
        ];
        $this->assertEquals(implode(PHP_EOL, $cmd) . PHP_EOL, $content);
    }

    public function testOptimizeMemory()
    {
        $composer = new Composer(__DIR__ . '/.data/loader.php', __DIR__ . '/fixture', null);

        $reflection = new \ReflectionClass(Composer::class);
        $property   = $reflection->getProperty('composer');

        $property->setAccessible(true);
        $property->setValue($composer, $this->createMock(Composer\Script::class));

        $composer->optimizeMemory();


        $this->assertTrue(file_exists(__DIR__ . '/.data/loader.php'));

        $content = file_get_contents(__DIR__ . '/.data/loader.php');

        $base = str_replace(DIRECTORY_SEPARATOR, '/', __DIR__);

        $cmd = [
            '<?php',
            '$loader = new Phalcon\Loader;',
            '$loader->registerFiles(' . var_export(array_values([
                '123456' => $base . '/fixture/files_A.php',
                '234567' => $base . '/fixture/files_B.php',
                '345678' => $base . '/fixture/files_C.php',
            ]), true) . ');',
            '$loader->registerDirs(' . var_export(array_values([
                $base . '/fixture/namespace/src',
                $base . '/fixture/namespace2/src',
                $base . '/fixture/namespace2/lib'
            ]), true) . ');',
            '$loader->registerNamespaces(' . var_export([
                'Namespace'  => [$base . '/fixture/namespace/src'],
                'Namespace2' => [
                    $base . '/fixture/namespace2/src',
                    $base . '/fixture/namespace2/lib'
                ],
                'Psr4'       => [$base . '/fixture/Psr4/src'],
            ], true) . ');',
            '$loader->registerClasses(' . var_export([
                'Class_A' => $base . '/fixture/Class_A.php',
                'Class_B' => $base . '/fixture/Class_B.php',
                'Class_C' => $base . '/fixture/Class_C.php',
            ], true) . ');',
            '$loader->register();'
        ];
        $this->assertEquals(implode(PHP_EOL, $cmd) . PHP_EOL, $content);
    }

    public function testOptimizeProcess()
    {
        $composer = new Composer(__DIR__ . '/.data/loader.php', __DIR__ . '/fixture', null);

        $reflection = new \ReflectionClass(Composer::class);
        $property   = $reflection->getProperty('composer');

        $property->setAccessible(true);
        $property->setValue($composer, $this->createMock(Composer\Script::class));

        $composer->optimizeProcess();


        $this->assertTrue(file_exists(__DIR__ . '/.data/loader.php'));

        $content = file_get_contents(__DIR__ . '/.data/loader.php');

        $base = str_replace(DIRECTORY_SEPARATOR, '/', __DIR__);

        $cmd = [
            '<?php',
            '$loader = new Phalcon\Loader;',
            '$loader->registerFiles(' . var_export(array_values([
                '123456' => $base . '/fixture/files_A.php',
                '234567' => $base . '/fixture/files_B.php',
                '345678' => $base . '/fixture/files_C.php',
            ]), true) . ');',
            '$loader->registerClasses(' . var_export([
                'Class_A' => $base . '/fixture/Class_A.php',
                'Class_B' => $base . '/fixture/Class_B.php',
                'Class_C' => $base . '/fixture/Class_C.php',
            ], true) . ');',
            '$loader->register();'
        ];
        $this->assertEquals(implode(PHP_EOL, $cmd) . PHP_EOL, $content);
    }
}

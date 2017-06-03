<?php

namespace Test\Cli\Tasks;

use Fake\Kernels\Cli\StubKernelCli;
use Test\TestCase\TestCase;

class OptimizeTaskTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // Force Enable Decoration for windows
        putenv('TERM=xterm');
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        // Force Enable Decoration for windows
        putenv('TERM=');
    }

    protected static function kernelClassInstance()
    {
        return StubKernelCli::class;
    }

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        foreach (glob(BASE_PATH . '/bootstrap/compile/*.php') as $file) {
            @unlink($file);
        }
        foreach (glob(BASE_PATH . '/vendor/composer/*.*') as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    private function mockAutoloadFiles($files)
    {
        file_put_contents(
            BASE_PATH . '/vendor/composer/autoload_files.php',
            '<?php' . PHP_EOL .
            'return ' . var_export($files, true) . ';'
        );
    }

    private function mockAutoloadNamespaces($namespaces)
    {
        file_put_contents(
            BASE_PATH . '/vendor/composer/autoload_namespaces.php',
            '<?php' . PHP_EOL .
            'return ' . var_export($namespaces, true) . ';'
        );
    }

    private function mockAutoloadPsr4($psr4)
    {
        file_put_contents(
            BASE_PATH . '/vendor/composer/autoload_psr4.php',
            '<?php' . PHP_EOL .
            'return ' . var_export($psr4, true) . ';'
        );
    }

    private function mockAutoloadClassmap($classmap)
    {
        file_put_contents(
            BASE_PATH . '/vendor/composer/autoload_classmap.php',
            '<?php' . PHP_EOL .
            'return ' . var_export($classmap, true) . ';'
        );
    }

    public function testTaskMemory()
    {
        $this->mockAutoloadFiles([
            'file_1.php',
            'file_2.php',
        ]);

        $this->mockAutoloadNamespaces([
            'namespace_1' => array('dir_1'),
            'namespace_2' => array('dir_2', 'dir_3'),
        ]);

        $this->mockAutoloadPsr4([
            'namespace_3' => array('dir_4'),
            'namespace_4' => array('dir_5', 'dir_6'),
        ]);

        $this->dispatchCli('quark optimize --memory -q');

        $file = file_get_contents(BASE_PATH . '/bootstrap/compile/loader.php');

        $this->assertEquals(
            '<?php' . PHP_EOL . '$loader = new Phalcon\Loader;' . PHP_EOL .
            '$loader->registerFiles(' . var_export(array_values([
                'file_1.php',
                'file_2.php',
            ]), true) . ');' . PHP_EOL .
            '$loader->registerDirs(' . var_export(array_values([
                'dir_1',
                'dir_2',
                'dir_3',
            ]), true) . ');' . PHP_EOL .
            '$loader->registerNamespaces(' . var_export([
                'namespace_1' => array('dir_1'),
                'namespace_2' => array('dir_2', 'dir_3'),
                'namespace_3' => array('dir_4'),
                'namespace_4' => array('dir_5', 'dir_6'),
            ], true) . ');' . PHP_EOL .
            '$loader->register();' . PHP_EOL,
            $file
        );
    }

    public function testTaskProcess()
    {
        $this->mockAutoloadFiles([
            'file_1.php',
            'file_2.php',
        ]);

        $this->mockAutoloadClassmap([
            'Class\Class_1' => 'file_1.php',
            'Class\Class_2' => 'file_2.php'
        ]);

        $this->dispatchCli('quark optimize -q');

        $file = file_get_contents(BASE_PATH . '/bootstrap/compile/loader.php');

        $this->assertEquals(
            '<?php' . PHP_EOL . '$loader = new Phalcon\Loader;' . PHP_EOL .
            '$loader->registerFiles(' . var_export(array_values([
                'file_1.php',
                'file_2.php',
            ]), true) . ');' . PHP_EOL .
            '$loader->registerClasses(' . var_export([
                'Class\Class_1' => 'file_1.php',
                'Class\Class_2' => 'file_2.php'
            ], true) . ');' . PHP_EOL .
            '$loader->register();' . PHP_EOL,
            $file
        );
    }
}

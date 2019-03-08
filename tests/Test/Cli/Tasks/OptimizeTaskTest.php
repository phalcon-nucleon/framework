<?php

namespace Test\Cli\Tasks;

use Fake\Kernels\Cli\StubKernelCli;
use Neutrino\Cli\Output\Decorate;
use Phalcon\Version;
use Test\TestCase\TestCase;

class OptimizeTaskTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        Decorate::setColorSupport(true);
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        Decorate::setColorSupport(null);
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

    private function mockComposerScript()
    {
        $mock = $this->mockService(\Neutrino\Optimizer\Composer\Script::class, \Neutrino\Optimizer\Composer\Script::class, false);

        $mock->expects($this->any())->method('dumpautoload')->willReturn(true);
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
            BASE_PATH . '/vendor/file_1.php',
            BASE_PATH . '/vendor/file_2.php',
        ]);

        $this->mockAutoloadNamespaces([
            'namespace_1' => array(BASE_PATH . '/vendor/dir_1'),
            'namespace_2' => array(BASE_PATH . '/vendor/dir_2', BASE_PATH . '/vendor/dir_3'),
        ]);

        $this->mockAutoloadPsr4([
            'namespace_3' => array(BASE_PATH . '/vendor/dir_4'),
            'namespace_4' => array(BASE_PATH . '/vendor/dir_5', BASE_PATH . '/vendor/dir_6'),
        ]);

        $this->mockComposerScript();

        $this->dispatchCli('quark optimize --memory --force -q');

        $file = file_get_contents(BASE_PATH . '/bootstrap/compile/loader.php');

        $this->assertEquals(
            implode("\n", array_filter([
                '<?php',
                "\$basePath = __DIR__ . '/../../';",
                "\$loader = new Phalcon\Loader;",
                (Version::getPart(Version::VERSION_MAJOR) >= 3 && Version::getPart(Version::VERSION_MEDIUM) >= 4)
                    ? '$loader->setFileCheckingCallback("stream_resolve_include_path");'
                    : '',
                "\$loader->registerFiles(array (",
                "  0 => \$basePath . 'vendor/file_1.php',",
                "  1 => \$basePath . 'vendor/file_2.php',",
                "));",
                "\$loader->registerDirs(array (",
                "  0 => \$basePath . 'vendor/dir_1',",
                "  1 => \$basePath . 'vendor/dir_2',",
                "  2 => \$basePath . 'vendor/dir_3',",
                "));",
                "\$loader->registerNamespaces(array (",
                "  'namespace_1' => ",
                "  array (",
                "    0 => \$basePath . 'vendor/dir_1',",
                "  ),",
                "  'namespace_2' => ",
                "  array (",
                "    0 => \$basePath . 'vendor/dir_2',",
                "    1 => \$basePath . 'vendor/dir_3',",
                "  ),",
                "  'namespace_3' => ",
                "  array (",
                "    0 => \$basePath . 'vendor/dir_4',",
                "  ),",
                "  'namespace_4' => ",
                "  array (",
                "    0 => \$basePath . 'vendor/dir_5',",
                "    1 => \$basePath . 'vendor/dir_6',",
                "  ),",
                "));",
                "\$loader->register();\n",
            ])),
            $file
        );
    }

    public function testTaskProcess()
    {
        $this->mockAutoloadFiles([
            BASE_PATH . '/vendor/file_1.php',
            BASE_PATH . '/vendor/file_2.php',
        ]);

        $this->mockAutoloadClassmap([
            'Class\Class_1' => BASE_PATH . '/vendor/file_1.php',
            'Class\Class_2' => BASE_PATH . '/vendor/file_2.php'
        ]);

        $this->mockComposerScript();

        $this->dispatchCli('quark optimize --force -q');

        $file = file_get_contents(BASE_PATH . '/bootstrap/compile/loader.php');

        $this->assertEquals(
            implode("\n", array_filter([
                "<?php",
                "\$basePath = __DIR__ . '/../../';",
                "\$loader = new Phalcon\Loader;",
                (Version::getPart(Version::VERSION_MAJOR) >= 3 && Version::getPart(Version::VERSION_MEDIUM) >= 4)
                    ? '$loader->setFileCheckingCallback("stream_resolve_include_path");'
                    : '',
                "\$loader->registerFiles(array (",
                "  0 => \$basePath . 'vendor/file_1.php',",
                "  1 => \$basePath . 'vendor/file_2.php',",
                "));",
                "\$loader->registerClasses(array (",
                "  'Class\\\\Class_1' => \$basePath . 'vendor/file_1.php',",
                "  'Class\\\\Class_2' => \$basePath . 'vendor/file_2.php',",
                "));",
                "\$loader->register();\n"
            ])),
            $file
        );
    }
}

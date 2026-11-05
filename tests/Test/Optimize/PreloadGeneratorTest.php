<?php

declare(strict_types=1);

namespace Test\Optimize;

use Neutrino\Foundation\Optimize\PreloadGenerator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PreloadGeneratorTest extends TestCase
{
    private string $basePath;

    private string $namespace;

    protected function setUp(): void
    {
        $id = bin2hex(random_bytes(6));
        $this->basePath = sys_get_temp_dir() . '/nucleon-preload-' . $id;
        // A namespace per test: classes loaded by one test cannot leak into another.
        $this->namespace = 'PreloadFixture' . $id;

        $classes = [
            'App\\Controller' => 'app/Controller.php',
            'App\\Broken' => 'app/Broken.php',
            'App\\Contract' => 'app/Contract.php',
            'Lib\\Service' => 'lib/Service.php',
            'Lib\\Internal\\Tool' => 'lib/Internal/Tool.php',
            'Other\\Thing' => 'other/Thing.php',
        ];

        $this->writeClass('app/Controller.php', 'App', 'class Controller implements Contract {}');
        $this->writeClass('app/Broken.php', 'App', 'class Broken extends \\' . $this->namespace . '\\Missing {}');
        $this->writeClass('app/Contract.php', 'App', 'interface Contract {}');
        $this->writeClass('lib/Service.php', 'Lib', 'final class Service {}');
        $this->writeClass('lib/Internal/Tool.php', 'Lib\\Internal', 'final class Tool {}');
        $this->writeClass('other/Thing.php', 'Other', 'final class Thing {}');

        $classmap = [];
        foreach ($classes as $class => $file) {
            $classmap[$this->namespace . '\\' . $class] = $this->basePath . '/' . $file;
        }

        mkdir($this->basePath . '/vendor/composer', 0777, true);
        file_put_contents(
            $this->basePath . '/vendor/composer/autoload_classmap.php',
            '<?php return ' . var_export($classmap, true) . ';',
        );
        file_put_contents($this->basePath . '/vendor/autoload.php', <<<'PHP'
            <?php
            $classmap = require __DIR__ . '/composer/autoload_classmap.php';
            spl_autoload_register(static function (string $class) use ($classmap): void {
                if (isset($classmap[$class])) {
                    require $classmap[$class];
                }
            });
            PHP);

        require $this->basePath . '/vendor/autoload.php';
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
    }

    public function testGenerateSelectsNamespacesAndAppClassesAndSkipsUnloadable(): void
    {
        $generator = new PreloadGenerator(
            $this->basePath,
            $this->basePath . '/vendor',
            namespaces: [$this->namespace . '\\Lib\\'],
            excludes: [$this->namespace . '\\Lib\\Internal\\'],
        );

        $result = $generator->generate();

        self::assertSame($this->basePath . PreloadGenerator::OUTPUT, $result->file);
        self::assertSame([
            $this->namespace . '\\App\\Contract',
            $this->namespace . '\\App\\Controller',
            $this->namespace . '\\Lib\\Service',
        ], $result->preloaded);
        self::assertSame([$this->namespace . '\\App\\Broken'], array_keys($result->skipped));
        self::assertStringContainsString('Missing', $result->skipped[$this->namespace . '\\App\\Broken']);
    }

    public function testGeneratedScriptLoadsTheClasses(): void
    {
        $result = (new PreloadGenerator(
            $this->basePath,
            $this->basePath . '/vendor',
            namespaces: [$this->namespace . '\\Lib\\'],
        ))->generate();

        $check = $this->basePath . '/check.php';
        file_put_contents($check, sprintf(
            '<?php require %s; echo json_encode(array_map("class_exists", %s));',
            var_export($result->file, true),
            var_export([$this->namespace . '\\App\\Controller', $this->namespace . '\\Lib\\Service'], true),
        ));

        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($check) . ' 2>&1', $output, $code);

        self::assertSame(0, $code, implode("\n", $output));
        self::assertSame('[true,true]', end($output));
    }

    public function testCustomPathsReplaceTheAppDirectory(): void
    {
        $result = (new PreloadGenerator(
            $this->basePath,
            $this->basePath . '/vendor',
            namespaces: [],
            paths: [$this->basePath . '/other'],
        ))->generate();

        self::assertSame([$this->namespace . '\\Other\\Thing'], $result->preloaded);
        self::assertSame([], $result->skipped);
    }

    public function testMissingClassmapIsReported(): void
    {
        unlink($this->basePath . '/vendor/composer/autoload_classmap.php');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('composer dump-autoload --classmap-authoritative');

        (new PreloadGenerator($this->basePath, $this->basePath . '/vendor'))->generate();
    }

    public function testClearRemovesTheScript(): void
    {
        $result = (new PreloadGenerator($this->basePath, $this->basePath . '/vendor', namespaces: []))->generate();

        PreloadGenerator::clear($this->basePath);
        PreloadGenerator::clear($this->basePath);

        self::assertFileDoesNotExist($result->file);
    }

    private function writeClass(string $file, string $namespace, string $code): void
    {
        $path = $this->basePath . '/' . $file;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, "<?php\n\nnamespace {$this->namespace}\\{$namespace};\n\n{$code}\n");
    }

    private function removeDirectory(string $dir): void
    {
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        ) as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}

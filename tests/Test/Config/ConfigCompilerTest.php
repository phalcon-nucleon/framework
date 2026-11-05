<?php

declare(strict_types=1);

namespace Test\Config;

use Neutrino\Config\ConfigCompiler;
use Neutrino\Config\Exception\UncacheableConfigException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConfigCompilerTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/nucleon-config-' . bin2hex(random_bytes(6));
        mkdir($this->basePath . '/config', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
    }

    public function testCompileEvaluatesAndAggregatesConfigFiles(): void
    {
        $this->writeConfig('app', "return ['name' => 'nucleon', 'path' => '/base' . '/storage', 'debug' => false];");
        $this->writeConfig('cache', "return ['default' => 'memory', 'stores' => ['memory' => ['ttl' => 60, 'ratio' => 1.5]]];");

        $file = ConfigCompiler::compile($this->basePath);

        self::assertSame($this->basePath . '/bootstrap/compile/config.php', $file);
        self::assertSame([
            'app' => ['name' => 'nucleon', 'path' => '/base/storage', 'debug' => false],
            'cache' => ['default' => 'memory', 'stores' => ['memory' => ['ttl' => 60, 'ratio' => 1.5]]],
        ], require $file);
    }

    public function testCompileSkipsExcludedFiles(): void
    {
        $this->writeConfig('app', "return ['name' => 'nucleon'];");
        $this->writeConfig('local', "return ['secret' => 'x'];");

        $config = require ConfigCompiler::compile($this->basePath, ['local']);

        self::assertSame(['app' => ['name' => 'nucleon']], $config);
    }

    public function testCompileAcceptsEnums(): void
    {
        $this->writeConfig('app', "return ['level' => \\Test\\Config\\Level::High];");

        $config = require ConfigCompiler::compile($this->basePath);

        self::assertSame(Level::High, $config['app']['level']);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function uncacheableValues(): iterable
    {
        yield 'closure' => ["return ['handlers' => ['error' => static fn () => null]];", 'app.handlers.error', 'Closure'];
        yield 'object' => ["return ['date' => new \\DateTimeImmutable()];", 'app.date', 'DateTimeImmutable'];
        yield 'stdClass' => ["return ['options' => (object) ['a' => 1]];", 'app.options', 'stdClass'];
    }

    #[DataProvider('uncacheableValues')]
    public function testCompileRejectsUncacheableValues(string $code, string $key, string $type): void
    {
        $this->writeConfig('app', $code);

        try {
            ConfigCompiler::compile($this->basePath);
            self::fail('An UncacheableConfigException was expected.');
        } catch (UncacheableConfigException $e) {
            self::assertSame('config/app.php', $e->configFile);
            self::assertSame($key, $e->key);
            self::assertSame($type, $e->type);
            self::assertStringContainsString('config/app.php', $e->getMessage());
        }

        self::assertFileDoesNotExist($this->basePath . ConfigCompiler::COMPILED_FILE);
    }

    public function testClearRemovesCompiledFile(): void
    {
        $this->writeConfig('app', "return [];");
        $file = ConfigCompiler::compile($this->basePath);

        ConfigCompiler::clear($this->basePath);
        ConfigCompiler::clear($this->basePath); // no error when already removed

        self::assertFileDoesNotExist($file);
    }

    private function writeConfig(string $name, string $code): void
    {
        file_put_contents($this->basePath . '/config/' . $name . '.php', "<?php\n\n" . $code . "\n");
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

enum Level: string
{
    case High = 'high';
}

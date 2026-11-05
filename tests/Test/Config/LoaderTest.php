<?php

declare(strict_types=1);

namespace Test\Config;

use Neutrino\Config\ConfigCompiler;
use Neutrino\Config\Loader;
use Phalcon\Config\Config;
use PHPUnit\Framework\TestCase;

final class LoaderTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/nucleon-config-loader-' . bin2hex(random_bytes(6));
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/config/app.php', "<?php return ['name' => 'nucleon', 'view' => ['implicit' => false]];");
        file_put_contents($this->basePath . '/config/local.php', "<?php return ['secret' => 'x'];");
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->basePath . '/{config,bootstrap/compile}/*.php', GLOB_BRACE) ?: []);
        @rmdir($this->basePath . '/bootstrap/compile');
        @rmdir($this->basePath . '/bootstrap');
        rmdir($this->basePath . '/config');
        rmdir($this->basePath);
    }

    public function testLoadFromFiles(): void
    {
        $config = Loader::load($this->basePath);

        self::assertInstanceOf(Config::class, $config);
        self::assertSame('nucleon', $config->path('app.name'));
        self::assertFalse($config->path('app.view.implicit'));
        self::assertSame('x', $config->path('local.secret'));
    }

    public function testExcludes(): void
    {
        self::assertSame(['app'], array_keys(Loader::raw($this->basePath, ['local'])));
        self::assertNull(Loader::fromFiles($this->basePath, ['local'])->get('local'));
    }

    public function testCompiledFileIsPreferred(): void
    {
        self::assertNull(Loader::fromCompile($this->basePath));

        ConfigCompiler::compile($this->basePath, ['local']);
        file_put_contents($this->basePath . '/config/app.php', "<?php return ['name' => 'changed'];");

        $config = Loader::load($this->basePath);

        self::assertSame('nucleon', $config->path('app.name'));
        self::assertNull($config->get('local'));
    }
}

<?php

declare(strict_types=1);

namespace Test\View\Volt;

use Neutrino\View\Engines\Volt\Compiler\FunctionExtend;
use Neutrino\View\Engines\Volt\VoltEngineRegister;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\View\ViewTestCase;

final class VoltEngineRegisterTest extends ViewTestCase
{
    public function testRegisterClosure(): void
    {
        $engine = VoltEngineRegister::getRegisterClosure()(new View(), $this->di);

        $this->assertInstanceOf(Volt::class, $engine);
        $this->assertSame(['path' => $this->dir . '/compiled/', 'separator' => '_', 'always' => APP_ENV === 'development' || APP_DEBUG], $engine->getOptions());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function options(): iterable
    {
        yield 'defaults' => [['compiled_path' => '/c/'], ['path' => '/c/', 'separator' => '_', 'always' => APP_ENV === 'development' || APP_DEBUG]];
        yield 'Volt 5 names' => [['compiled_path' => '/c/', 'options' => ['always' => false, 'stat' => false]], ['path' => '/c/', 'separator' => '_', 'always' => false, 'stat' => false]];
        yield '1.3 names' => [
            ['options' => ['compiledPath' => '/p/', 'compiledSeparator' => '%', 'compiledExtension' => '.php', 'compileAlways' => false]],
            ['separator' => '%', 'always' => false, 'path' => '/p/', 'extension' => '.php'],
        ];
    }

    /**
     * @param array<string, mixed> $view
     * @param array<string, mixed> $expected
     */
    #[DataProvider('options')]
    public function testOptions(array $view, array $expected): void
    {
        $this->assertEquals($expected, VoltEngineRegister::options($view));
    }

    public function testNoDeprecatedOption(): void
    {
        $this->container(['options' => ['compileAlways' => true]]);

        set_error_handler(static fn(int $errno, string $error): bool => throw new \ErrorException($error));
        try {
            $this->compiler()->compileString('{{ 1 }}');
        } finally {
            restore_error_handler();
        }

        $this->addToAssertionCount(1);
    }

    public function testExtensionsFiltersAndFunctions(): void
    {
        $this->container(['functions' => ['route' => \Neutrino\View\Engines\Volt\Compiler\Functions\RouteFunction::class, 'stub' => StubFunction::class]]);
        $compiler = $this->compiler();

        $this->assertCount(3, $compiler->getExtensions());
        $this->assertSame(['round', 'merge', 'split'], array_keys($compiler->getFilters()));
        $this->assertSame(['dump', 'route', 'stub'], array_keys($compiler->getFunctions()));
        $this->assertSame('<?= stub_fn(1.25) ?>', $compiler->compileString('{{ stub(1.25) }}'));
        $this->assertSame('<?= \Neutrino\Debug\VarDump::dump(1) ?>', $compiler->compileString('{{ dump(1) }}'));
    }
}

final class StubFunction extends FunctionExtend
{
    public function compileFunction(string $resolvedArgs, ?array $exprArgs): string
    {
        return 'stub_fn(' . $resolvedArgs . ')';
    }
}

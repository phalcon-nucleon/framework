<?php

declare(strict_types=1);

namespace Test\View\Volt;

use Neutrino\Constants\Services;
use Neutrino\Http\Middleware\Csrf;
use Neutrino\Providers;
use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\View\Engines\Volt\Compiler\Extensions\PhpFunctionExtension;
use Phalcon\Mvc\Router;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase\ArraySession;
use Test\View\ViewTestCase;

/**
 * The Nucleon extensions and functions, compiled and rendered.
 */
final class ExtensionsTest extends ViewTestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function strTemplates(): iterable
    {
        yield 'str_* function' => ['{{ str_snake("NucleonFramework") }}', 'nucleon_framework'];
        yield 'str_* with arguments' => ['{{ str_limit("Nucleon", 3, "!") }}', 'Nuc!'];
        yield 'PHP function first' => ['{{ str_replace("a", "o", "Nucleaan") }}', 'Nucleoon'];
        yield 'slug filter' => ['{{ "Hello World"|slug }}', 'hello-world'];
        yield 'limit filter' => ['{{ "Nucleon"|limit(3) }}', 'Nuc...'];
        yield 'words filter' => ['{{ "one two three"|words(2) }}', 'one two...'];
        yield 'PHP function' => ['{{ ucfirst("nucleon") }}', 'Nucleon'];
    }

    #[DataProvider('strTemplates')]
    public function testRender(string $template, string $expected): void
    {
        $this->assertSame($expected, $this->renderString($template));
    }

    public function testStrCompilesToStr(): void
    {
        $this->assertSame('<?= \Neutrino\Support\Str::slug(\'a\') ?>', $this->compiler()->compileString('{{ str_slug("a") }}'));
        $this->assertSame('<?= \Neutrino\Support\Str::slug($title) ?>', $this->compiler()->compileString('{{ title|slug }}'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function deniedFunctions(): iterable
    {
        foreach (['system', 'exec', 'unlink', 'file_put_contents', 'ini_set', 'putenv', 'call_user_func', 'array_map', 'SYSTEM'] as $function) {
            yield $function => [$function];
        }
    }

    #[DataProvider('deniedFunctions')]
    public function testDeniedFunctionsAreNotCompiled(string $function): void
    {
        $compiled = $this->compiler()->compileString("{{ $function('x') }}");

        $this->assertStringNotContainsString($function . '(', $compiled);
        $this->assertStringContainsString('callMacro', $compiled, 'Volt sees an unknown function.');
    }

    public function testDenyAndAllowLists(): void
    {
        $this->container(['php_functions' => ['deny' => ['ucfirst'], 'allow' => ['array_map']]]);
        $compiler = $this->compiler();

        $this->assertStringNotContainsString('ucfirst(', $compiler->compileString('{{ ucfirst("a") }}'));
        $this->assertStringContainsString('array_map(', $compiler->compileString('{{ array_map("trim", list) }}'));
        $this->assertStringContainsString('system(', $compiler->compileString('{{ system("ls") }}'), 'deny replaces the default list.');
    }

    public function testDefaultDenyListOnlyHoldsExistingFunctionNames(): void
    {
        $this->assertSame(PhpFunctionExtension::DENY, array_values(array_unique(PhpFunctionExtension::DENY)));
    }

    public function testCsrf(): void
    {
        $this->di->setShared(Services::SESSION, new ArraySession());
        ProviderRegistrar::register($this->di, [Providers\Security::class]);

        $field = $this->renderString('{{ csrf_field() }}');
        $token = $this->renderString('{{ csrf_token() }}');

        $this->assertSame(Csrf::token($this->di), $token);
        $this->assertSame('<input type="hidden" name="' . Csrf::FIELD . '" value="' . htmlspecialchars($token, ENT_QUOTES) . '">', $field);
        $this->assertSame($token, $this->renderString('{{ csrf_token() }}'), 'The token of the session is kept.');
    }

    public function testCsrfValueIsEscaped(): void
    {
        $this->di->setShared(Services::SESSION, $session = new ArraySession());
        ProviderRegistrar::register($this->di, [Providers\Security::class]);
        $session->set('$PHALCON/CSRF$', '"><script>');

        $this->assertSame('<input type="hidden" name="_csrf_token" value="&quot;&gt;&lt;script&gt;">', $this->renderString('{{ csrf_field() }}'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function routes(): iterable
    {
        yield 'name' => ["{{ route('home') }}", '/'];
        yield 'parameters' => ["{{ route('user', {'id': 12}) }}", '/users/12'];
        yield 'variable parameters' => ["{{ route('user', ['id': id]) }}", '/users/7'];
        yield 'query' => ["{{ route('user', {'id': 12}, {'tab': 'posts'}) }}", '/users/12?tab=posts'];
        yield 'no parameters, query' => ["{{ route('home', null, {'q': 'a'}) }}", '/?q=a'];
    }

    #[DataProvider('routes')]
    public function testRoute(string $template, string $expected): void
    {
        $router = new Router(false);
        $router->add('/', ['controller' => 'index'])->setName('home');
        $router->add('/users/{id}', ['controller' => 'users'])->setName('user');
        $this->di->setShared(Services::ROUTER, $router);

        $this->assertSame($expected, $this->renderString($template, ['id' => 7]));
    }
}

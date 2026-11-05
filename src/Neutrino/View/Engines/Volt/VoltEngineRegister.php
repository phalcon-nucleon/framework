<?php

declare(strict_types=1);

namespace Neutrino\View\Engines\Volt;

use Neutrino\Constants\Env;
use Neutrino\Constants\Services;
use Neutrino\View\Engines\EngineRegister;
use Neutrino\View\Engines\Volt\Compiler\FilterExtend;
use Neutrino\View\Engines\Volt\Compiler\FunctionExtend;
use Phalcon\Config\Config;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\View\Engine\Volt;
use Phalcon\Mvc\ViewBaseInterface;

/**
 * The Volt engine, configured by `config/view.php`:
 * - `compiled_path`: directory of the compiled templates;
 * - `options`: Volt options (`path`, `separator`, `always`, `stat`, `extension`…). The Nucleon 1.3 names
 *   (`compiledPath`, `compiledSeparator`, `compiledExtension`, `compileAlways`), deprecated by Phalcon 5,
 *   are converted. `always` defaults to `true` in development or debug;
 * - `extensions` (list of classes), `functions` and `filters` (`name => class`).
 */
class VoltEngineRegister extends EngineRegister
{
    /**
     * Volt options renamed by Phalcon 4.
     *
     * @var array<string, string>
     */
    private const array RENAMED_OPTIONS = [
        'compiledPath'      => 'path',
        'compiledSeparator' => 'separator',
        'compiledExtension' => 'extension',
        'compileAlways'     => 'always',
    ];

    public function register(ViewBaseInterface $view, DiInterface $di): Volt
    {
        $volt = new Volt($view, $di);

        /** @var Config $config */
        $config = $di->getShared(Services::CONFIG);
        $settings = $config->path('view');
        $settings = $settings instanceof Config ? $settings->toArray() : [];

        $volt->setOptions(self::options($settings));

        $compiler = $volt->getCompiler();

        foreach (self::list($settings['extensions'] ?? []) as $extension) {
            $compiler->addExtension(new $extension($compiler));
        }

        foreach (self::list($settings['filters'] ?? []) as $name => $class) {
            /** @var FilterExtend $filter */
            $filter = new $class($compiler);
            $compiler->addFilter((string) $name, static fn(string $resolvedArgs, ?array $exprArgs): string => (string) $filter->compileFilter($resolvedArgs, $exprArgs));
        }

        $compiler->addFunction('dump', '\Neutrino\Debug\VarDump::dump');

        foreach (self::list($settings['functions'] ?? []) as $name => $class) {
            /** @var FunctionExtend $function */
            $function = new $class($compiler);
            $compiler->addFunction((string) $name, static fn(string $resolvedArgs, ?array $exprArgs): string => (string) $function->compileFunction($resolvedArgs, $exprArgs));
        }

        return $volt;
    }

    /**
     * @param array<mixed> $view
     *
     * @return array<string, mixed>
     */
    public static function options(array $view): array
    {
        $options = [
            'path'      => $view['compiled_path'] ?? null,
            'separator' => '_',
            'always'    => APP_ENV === Env::DEVELOPMENT || APP_DEBUG,
        ];

        foreach (is_array($view['options'] ?? null) ? $view['options'] : [] as $name => $value) {
            $options[self::RENAMED_OPTIONS[$name] ?? (string) $name] = $value;
        }

        return array_filter($options, static fn(mixed $value): bool => $value !== null);
    }

    /**
     * @return array<int|string, class-string>
     */
    private static function list(mixed $classes): array
    {
        /** @var array<int|string, class-string> */
        return is_array($classes) ? array_filter($classes, 'is_string') : [];
    }
}

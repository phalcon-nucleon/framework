<?php

declare(strict_types=1);

namespace Neutrino\Dotconst;

use Neutrino\Dotconst;
use Neutrino\Support\AtomicFile;

/**
 * Compiles the `.const.ini` files into a PHP file of constant declarations.
 *
 * Values are written as `const X = ...;` (resolved by the engine, slightly faster than `define()`),
 * except the ones that must be evaluated at runtime, such as `@php/env`, which use `define()`.
 */
final class Compile
{
    /**
     * @param string $basePath    Directory of the `.const.ini` files
     * @param string $compilePath Directory of the compiled `consts.php`
     *
     * @return string Path of the compiled file
     *
     * @throws Exception\InvalidFileException
     */
    public static function compile(string $basePath, string $compilePath): string
    {
        $raw = Loader::loadRaw($basePath);

        $lines = [];
        $nested = [];

        foreach ($raw as $const => $value) {
            foreach (Dotconst::getExtensions() as $extension) {
                if ($extension->identify($value)) {
                    /** @var string $value */
                    $lines[] = self::declare($const, $extension->compile($value, $basePath, $compilePath), $extension->isConstantExpression());

                    continue 2;
                }
            }

            if (($reference = Loader::matchReference($value)) !== null) {
                [$name, $rest] = $reference;
                $key = strtoupper($name);

                if (array_key_exists($key, $raw)) {
                    $nested[$const] = [
                        'require' => $key,
                        'draw'    => $rest === '' ? '\\' . $key : '\\' . $key . ' . ' . var_export($rest, true),
                    ];
                } else {
                    $nested[$const] = ['require' => null, 'draw' => var_export($name . $rest, true)];
                }

                continue;
            }

            $lines[] = self::declare($const, var_export($value, true), true);
        }

        foreach (Helper::nestedConstSort($nested) as $const => $item) {
            $lines[] = self::declare($const, $item['draw'], true);
        }

        $file = $compilePath . DIRECTORY_SEPARATOR . Loader::COMPILED_FILE;

        AtomicFile::write($file, "<?php\n\n" . implode("\n", $lines) . "\n");

        return $file;
    }

    private static function declare(string $const, string $expression, bool $constantExpression): string
    {
        if ($constantExpression && preg_match('/^[A-Za-z_]\w*$/', $const) === 1) {
            return "const $const = $expression;";
        }

        return 'define(' . var_export($const, true) . ", $expression);";
    }
}

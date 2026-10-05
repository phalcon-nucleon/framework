<?php

declare(strict_types=1);

namespace Neutrino\Cli\Output;

/**
 * ANSI colors and styles of the console output.
 *
 * Colors are used when STDOUT is a terminal and `NO_COLOR` is not set (https://no-color.org);
 * the `--colors` / `--no-colors` options (setColorSupport()) take precedence.
 */
final class Decorate
{
    private const array FOREGROUND = [
        'black'   => [30, 39],
        'red'     => [31, 39],
        'green'   => [32, 39],
        'yellow'  => [33, 39],
        'blue'    => [34, 39],
        'magenta' => [35, 39],
        'cyan'    => [36, 39],
        'white'   => [37, 39],
        'default' => [39, 39],
    ];

    private const array BACKGROUND = [
        'black'   => [40, 49],
        'red'     => [41, 49],
        'green'   => [42, 49],
        'yellow'  => [43, 49],
        'blue'    => [44, 49],
        'magenta' => [45, 49],
        'cyan'    => [46, 49],
        'white'   => [47, 49],
        'default' => [49, 49],
    ];

    private const array OPTIONS = [
        'bold'       => [1, 22],
        'underscore' => [4, 24],
        'blink'      => [5, 25],
        'reverse'    => [7, 27],
        'conceal'    => [8, 28],
    ];

    private static ?bool $hasColorSupport = null;

    public static function setColorSupport(?bool $support): void
    {
        self::$hasColorSupport = $support;
    }

    public static function hasColorSupport(): bool
    {
        return self::$hasColorSupport ??= self::detectColorSupport();
    }

    /**
     * @param list<string> $options bold, underscore, blink, reverse, conceal
     */
    public static function apply(string $text, ?string $foreground = null, ?string $background = null, array $options = []): string
    {
        if (!self::hasColorSupport()) {
            return $text;
        }

        $codes = [];
        if ($foreground !== null) {
            $codes[] = self::FOREGROUND[$foreground];
        }
        if ($background !== null) {
            $codes[] = self::BACKGROUND[$background];
        }
        foreach ($options as $option) {
            $codes[] = self::OPTIONS[$option];
        }

        if ($codes === []) {
            return $text;
        }

        return sprintf("\033[%sm%s\033[%sm", implode(';', array_column($codes, 0)), $text, implode(';', array_column($codes, 1)));
    }

    public static function info(string $str): string
    {
        return self::apply($str, 'green');
    }

    public static function notice(string $str): string
    {
        return self::apply($str, 'yellow');
    }

    public static function warn(string $str): string
    {
        return self::apply($str, 'yellow', null, ['reverse']);
    }

    public static function error(string $str): string
    {
        return self::apply($str, 'black', 'red');
    }

    public static function question(string $str): string
    {
        return self::apply($str, 'black', 'cyan');
    }

    private static function detectColorSupport(): bool
    {
        $noColor = getenv('NO_COLOR');
        if ($noColor !== false && $noColor !== '') {
            return false;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            return (function_exists('sapi_windows_vt100_support') && @sapi_windows_vt100_support(STDOUT))
                || getenv('ANSICON') !== false
                || getenv('ConEmuANSI') === 'ON'
                || getenv('TERM') === 'xterm';
        }

        return @stream_isatty(STDOUT);
    }
}

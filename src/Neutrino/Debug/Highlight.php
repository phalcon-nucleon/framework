<?php

declare(strict_types=1);

namespace Neutrino\Debug;

use Tempest\Highlight\Highlighter;
use Tempest\Highlight\Themes\LightTerminalTheme;

/**
 * Syntax highlighting of PHP and SQL, with `tempest/highlight` when installed (a suggested dependency).
 * Without it, the code is returned as is (escaped for HTML).
 *
 * The HTML uses the `hl-*` classes of `tempest/highlight`, styled by {@see self::css()}.
 */
final class Highlight
{
    /**
     * Forces the absence of `tempest/highlight` (tests).
     *
     * @internal
     */
    public static bool $disabled = false;

    private function __construct() {}

    public static function available(): bool
    {
        return !self::$disabled && class_exists(Highlighter::class);
    }

    /**
     * Code as HTML.
     *
     * @param 'php'|'sql'|string $language
     */
    public static function html(string $code, string $language): string
    {
        if (!self::available()) {
            return htmlspecialchars($code);
        }

        return (new Highlighter())->parse($code, $language);
    }

    /**
     * Code with the colors of the terminal (ANSI).
     *
     * @param 'php'|'sql'|string $language
     */
    public static function terminal(string $code, string $language): string
    {
        if (!self::available()) {
            return $code;
        }

        return (new Highlighter(new LightTerminalTheme()))->parse($code, $language);
    }

    /**
     * The lines of a PHP file around a line, as HTML with their numbers. The line is marked by the
     * `hl-current` class. Empty when the file cannot be read.
     */
    public static function fileFragment(string $file, int $line, int $around = 8): string
    {
        $lines = is_file($file) ? file($file, FILE_IGNORE_NEW_LINES) : false;

        if ($lines === false || $line < 1) {
            return '';
        }

        $start = max(1, $line - $around);
        // A space keeps the empty lines at the start, that the gutter of tempest/highlight would trim.
        $fragment = array_map(static fn(string $row): string => $row === '' ? ' ' : $row, array_slice($lines, $start - 1, $around * 2 + 1));

        if (!self::available()) {
            $rows = [];
            $width = strlen((string) ($start + count($fragment) - 1));

            foreach ($fragment as $i => $row) {
                $number = $start + $i;
                $rows[] = '<span class="hl-gutter' . ($number === $line ? ' hl-current' : '') . '">' . str_pad((string) $number, $width, ' ', STR_PAD_LEFT) . '</span>' . htmlspecialchars($row);
            }

            return implode("\n", $rows);
        }

        $highlighter = (new Highlighter())->withGutter($start);
        $highlighter->getGutterInjection()?->addClass($line - $start + 1, 'hl-current');

        return $highlighter->parse(implode("\n", $fragment), 'php');
    }

    /**
     * The CSS of the highlighted HTML.
     */
    public static function css(): string
    {
        return '.hl-keyword{color:#cf222e}.hl-property{color:#8250df}.hl-type{color:#953800}.hl-generic{color:#8250df}'
            . '.hl-value,.hl-literal{color:#0a3069}.hl-number{color:#0550ae}.hl-variable{color:#953800}'
            . '.hl-comment{color:#6e7781;font-style:italic}.hl-attribute{font-style:italic}'
            . '.hl-gutter{display:inline-block;margin-right:1em;padding-right:.5em;color:#8c959f;border-right:1px solid #d0d7de;user-select:none}'
            . '.hl-gutter.hl-current{color:#fff;background:#cf222e}';
    }
}

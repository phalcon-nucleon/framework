<?php

declare(strict_types=1);

namespace Neutrino\Dotconst;

use Neutrino\Dotconst\Exception\CycleNestedConstException;
use Neutrino\Dotconst\Exception\InvalidFileException;

/**
 * @internal
 */
final class Helper
{
    /**
     * Reads an ini file and flattens its sections: `[APP] env = x` gives `APP_ENV => x`.
     *
     * @return array<string, scalar|null>
     *
     * @throws InvalidFileException
     */
    public static function loadIniFile(string $file): array
    {
        $config = @parse_ini_file($file, true, INI_SCANNER_TYPED);

        if ($config === false) {
            throw new InvalidFileException('Failed parse file : ' . $file);
        }

        return array_change_key_case(self::definable($config), CASE_UPPER);
    }

    /**
     * @param array<string, scalar|null> $config
     *
     * @return array<string, scalar|null>
     *
     * @throws InvalidFileException
     */
    public static function mergeConfigWithFile(array $config, string $file): array
    {
        return array_replace($config, self::loadIniFile($file));
    }

    /**
     * Sorts the nested constants so that each one comes after the constant it requires.
     *
     * @template T of array{require: ?string}
     *
     * @param array<string, T> $nested
     *
     * @return array<string, T>
     *
     * @throws CycleNestedConstException
     */
    public static function nestedConstSort(array $nested): array
    {
        uasort($nested, static fn(array $a, array $b): int => self::compareNested($nested, $a['require'], $b['require'], 0));

        return $nested;
    }

    /**
     * @param array<string, array{require: ?string}> $nested
     */
    private static function compareNested(array $nested, ?string $a, ?string $b, int $depth): int
    {
        if ($depth >= 128) {
            throw new CycleNestedConstException();
        }

        return match (true) {
            $a === null && $b === null => 0,
            $a === null => -1,
            $b === null => 1,
            isset($nested[$a], $nested[$b]) => self::compareNested($nested, $nested[$a]['require'], $nested[$b]['require'], $depth + 1),
            isset($nested[$a]) => 1,
            isset($nested[$b]) => -1,
            default => 0,
        };
    }

    /**
     * @param array<mixed> $config
     *
     * @return array<string, scalar|null>
     */
    private static function definable(array $config): array
    {
        $flatten = [];
        foreach ($config as $section => $value) {
            if (is_array($value)) {
                foreach (self::definable($value) as $k => $v) {
                    $flatten["{$section}_{$k}"] = $v;
                }
            } else {
                /** @var scalar|null $value */
                $flatten[(string) $section] = $value;
            }
        }

        return $flatten;
    }
}

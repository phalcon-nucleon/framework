<?php

declare(strict_types=1);

namespace Neutrino\Support;

/**
 * File path helpers.
 */
final class Path
{
    /**
     * Normalize a path: resolves "." and ".." segments, removes duplicate
     * separators, and uses DIRECTORY_SEPARATOR. The path is not required to exist.
     */
    public static function normalize(string $path): string
    {
        if ($path === '') {
            return '';
        }

        $parts = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $path));
        $safe = [];

        foreach ($parts as $idx => $part) {
            if ($idx === 0 && $part === '') {
                $safe[] = '';
            } elseif (trim($part) === '' || $part === '.') {
                continue;
            } elseif ($part === '..') {
                if (array_pop($safe) === null || $safe === []) {
                    $safe[] = '';
                }
            } else {
                $safe[] = $part;
            }
        }

        if ($safe === ['']) {
            return DIRECTORY_SEPARATOR;
        }

        return implode(DIRECTORY_SEPARATOR, $safe);
    }

    /**
     * Find the relative path from $fromPath to $toPath.
     */
    public static function findRelative(string $fromPath, string $toPath): string
    {
        $from = explode(DIRECTORY_SEPARATOR, self::normalize(str_replace(DIRECTORY_SEPARATOR, '/', $fromPath)));
        $to = explode(DIRECTORY_SEPARATOR, self::normalize(str_replace(DIRECTORY_SEPARATOR, '/', $toPath)));

        $relPath = '';

        $i = 0;
        while (isset($from[$i], $to[$i])) {
            if ($from[$i] !== $to[$i]) {
                break;
            }
            $i++;
        }

        $j = count($from) - 1;
        while ($i <= $j) {
            if ($from[$j] !== '') {
                $relPath .= '../';
            }
            $j--;
        }

        while (isset($to[$i])) {
            if ($to[$i] !== '') {
                $relPath .= $to[$i] . '/';
            }
            $i++;
        }

        return substr($relPath, 0, -1);
    }
}

<?php

declare(strict_types=1);

namespace Neutrino\Support;

use RuntimeException;

/**
 * Writes files atomically: a reader (or OPcache) never sees a partial file.
 */
final class AtomicFile
{
    public static function write(string $path, string $content): void
    {
        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Cannot create directory "%s".', $dir));
        }

        $tmp = tempnam($dir, '.tmp-');

        if ($tmp === false || file_put_contents($tmp, $content) === false) {
            throw new RuntimeException(sprintf('Cannot write "%s".', $path));
        }

        chmod($tmp, 0664);

        if (!rename($tmp, $path)) {
            @unlink($tmp);

            throw new RuntimeException(sprintf('Cannot write "%s".', $path));
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }
}

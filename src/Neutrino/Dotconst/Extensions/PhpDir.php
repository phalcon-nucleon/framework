<?php

declare(strict_types=1);

namespace Neutrino\Dotconst\Extensions;

use Neutrino\Support\Path;

/**
 * `@php/dir[:/sub/path][@suffix]`: path relative to the directory of the ini file.
 */
final class PhpDir extends Extension
{
    protected string $identifier = 'php/dir(?::(/[\w\-. ]+))?(?:@(.+))?';

    public function parse(string $value, string $basePath): string
    {
        $match = $this->match($value);

        return Path::normalize($basePath . DIRECTORY_SEPARATOR . ($match[1] ?? '') . ($match[2] ?? ''));
    }

    public function compile(string $value, string $basePath, string $compilePath): string
    {
        $match = $this->match($value);

        // Relative to the compiled file, so that the application directory can be moved after compilation.
        $relative = Path::findRelative($compilePath, $basePath);
        $path = ($relative === '' ? '' : '/' . $relative) . ($match[1] ?? '') . ($match[2] ?? '');

        return $path === '' ? '__DIR__' : '__DIR__ . ' . var_export($path, true);
    }
}

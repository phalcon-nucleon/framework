<?php

declare(strict_types=1);

namespace Neutrino\Config;

use Phalcon\Config\Config;

/**
 * Loads the application configuration: the compiled file when it exists, the `config/*.php` files otherwise.
 */
final class Loader
{
    /**
     * @param list<string> $excludes Names of the config files to skip (without `.php`)
     */
    public static function load(string $basePath, array $excludes = []): Config
    {
        return self::fromCompile($basePath) ?? self::fromFiles($basePath, $excludes);
    }

    /**
     * @param list<string> $excludes Names of the config files to skip (without `.php`)
     *
     * @return array<string, mixed>
     */
    public static function raw(string $basePath, array $excludes = []): array
    {
        $config = [];

        $excludes = array_flip($excludes);

        foreach (glob($basePath . '/config/*.php') ?: [] as $file) {
            if (!isset($excludes[$fileName = basename($file, '.php')])) {
                $config[$fileName] = require $file;
            }
        }

        return $config;
    }

    /**
     * @param list<string> $excludes Names of the config files to skip (without `.php`)
     */
    public static function fromFiles(string $basePath, array $excludes = []): Config
    {
        return new Config(self::raw($basePath, $excludes));
    }

    public static function fromCompile(string $basePath): ?Config
    {
        if (is_file($compilePath = $basePath . ConfigCompiler::COMPILED_FILE)) {
            /** @var array<string, mixed> $config */
            $config = require $compilePath;

            return new Config($config);
        }

        return null;
    }
}

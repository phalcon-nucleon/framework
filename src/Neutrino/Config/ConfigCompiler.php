<?php

declare(strict_types=1);

namespace Neutrino\Config;

use Neutrino\Support\AtomicFile;
use UnitEnum;

/**
 * Compiles the application configuration (config/*.php) into a single cached
 * file, read back by Loader::fromCompile().
 *
 * Configuration files are evaluated: expressions such as BASE_PATH . '/x' are
 * resolved at compile time. Values that cannot be exported to PHP code
 * (closures, objects other than enums, resources) are rejected.
 */
final class ConfigCompiler
{
    public const string COMPILED_FILE = '/bootstrap/compile/config.php';

    /**
     * @param list<string> $excludes Configuration file names (without .php) to skip.
     *
     * @return string The path of the compiled file.
     *
     * @throws Exception\UncacheableConfigException
     */
    public static function compile(string $basePath, array $excludes = []): string
    {
        $config = [];
        $excludes = array_flip($excludes);

        foreach (glob($basePath . '/config/*.php') ?: [] as $file) {
            $name = basename($file, '.php');

            if (isset($excludes[$name])) {
                continue;
            }

            $config[$name] = require $file;

            self::assertExportable($config[$name], 'config/' . $name . '.php', $name);
        }

        $output = $basePath . self::COMPILED_FILE;

        AtomicFile::write($output, "<?php\n\nreturn " . var_export($config, true) . ";\n");

        return $output;
    }

    /**
     * Removes the compiled configuration, if any.
     */
    public static function clear(string $basePath): void
    {
        $file = $basePath . self::COMPILED_FILE;

        if (is_file($file)) {
            unlink($file);
        }
    }

    private static function assertExportable(mixed $value, string $file, string $path): void
    {
        if ($value === null || is_scalar($value) || $value instanceof UnitEnum) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                self::assertExportable($item, $file, $path . '.' . $key);
            }

            return;
        }

        throw new Exception\UncacheableConfigException($file, $path, get_debug_type($value));
    }
}

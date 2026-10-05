<?php

declare(strict_types=1);

namespace Neutrino\Dotconst;

use Neutrino\Dotconst;

/**
 * Reads the constants from the compiled file, or from the `.const.ini` and `.const.{APP_ENV}.ini` files.
 */
final class Loader
{
    public const string COMPILED_FILE = 'consts.php';

    /**
     * Includes the compiled constants file if it exists.
     */
    public static function fromCompile(string $path): bool
    {
        if (is_file($compilePath = $path . '/' . self::COMPILED_FILE)) {
            require $compilePath;

            return true;
        }

        return false;
    }

    /**
     * Reads and resolves the `.const.ini` and `.const.{APP_ENV}.ini` files of a directory.
     *
     * @return array<string, scalar|null>
     *
     * @throws Exception\InvalidFileException
     */
    public static function fromFiles(string $path): array
    {
        if (!is_file($path . DIRECTORY_SEPARATOR . '.const.ini')) {
            return [];
        }

        return self::dynamize(self::loadRaw($path), $path);
    }

    /**
     * Reads the ini files without resolving the extensions and references.
     *
     * @return array<string, scalar|null>
     *
     * @throws Exception\InvalidFileException
     */
    public static function loadRaw(string $path): array
    {
        $basePath = $path . DIRECTORY_SEPARATOR . '.const';

        if (!is_file($basePath . '.ini')) {
            return [];
        }

        $raw = Helper::loadIniFile($basePath . '.ini');

        $env = self::environment($raw, $path);

        if (is_string($env) && $env !== '' && is_file($pathEnv = $basePath . '.' . $env . '.ini')) {
            $raw = Helper::mergeConfigWithFile($raw, $pathEnv);
        }

        return $raw;
    }

    /**
     * Parses a reference to another constant: `@{name}rest`.
     *
     * @return array{string, string}|null The referenced name and the rest of the value
     */
    public static function matchReference(mixed $value): ?array
    {
        if (is_string($value) && preg_match('#^@\{(\w+)\}@?#', $value, $match) === 1) {
            return [$match[1], substr($value, strlen($match[0]))];
        }

        return null;
    }

    /**
     * Value of `[APP] env`, resolving only that constant (it selects the `.const.{env}.ini` file).
     *
     * @param array<string, scalar|null> $raw
     */
    private static function environment(array $raw, string $dir): mixed
    {
        $env = $raw['APP_ENV'] ?? null;

        if (self::matchReference($env) !== null) {
            return self::dynamize($raw, $dir)['APP_ENV'];
        }

        foreach (Dotconst::getExtensions() as $extension) {
            if ($extension->identify($env)) {
                /** @var string $env */
                return $extension->parse($env, $dir);
            }
        }

        return $env;
    }

    /**
     * Resolves the extensions, then the references.
     *
     * @param array<string, scalar|null> $config
     *
     * @return array<string, scalar|null>
     */
    private static function dynamize(array $config, string $dir): array
    {
        foreach (Dotconst::getExtensions() as $extension) {
            foreach ($config as $const => $value) {
                if ($extension->identify($value)) {
                    /** @var string $value */
                    $config[$const] = $extension->parse($value, $dir);
                }
            }
        }

        $nested = [];
        foreach ($config as $const => $value) {
            if (($reference = self::matchReference($value)) !== null) {
                [$name, $rest] = $reference;
                $key = strtoupper($name);

                $nested[$const] = isset($config[$key])
                    ? ['require' => $key, 'value' => $rest]
                    : ['require' => null, 'value' => $name . $rest];
            }
        }

        foreach (Helper::nestedConstSort($nested) as $const => $item) {
            $resolved = $item['require'] !== null ? $config[$item['require']] : null;

            $config[$const] = $item['value'] === '' ? $resolved : $resolved . $item['value']; // @phpstan-ignore binaryOp.invalid (scalar|null)
        }

        /** @var array<string, scalar|null> $config */
        return $config;
    }
}

<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Optimize;

use Neutrino\Support\AtomicFile;
use Neutrino\Support\Path;
use RuntimeException;
use Throwable;

/**
 * Generates a script for `opcache.preload`.
 *
 * Classes are taken from the Composer classmap (run `composer dump-autoload
 * --classmap-authoritative` first): Nucleon classes used to serve requests,
 * and the application classes. Each candidate is loaded once at generation
 * time; classes that cannot be loaded (missing parent, optional dependency
 * not installed...) are skipped, so that the preload script can never break
 * the server start. A class that PHP cannot compile (e.g. a signature
 * incompatible with its parent) is a fatal error that cannot be caught: it
 * stops the generation, with PHP's message, instead of the server start.
 */
final class PreloadGenerator
{
    public const string OUTPUT = '/bootstrap/compile/preload.php';

    /**
     * Nucleon namespaces not needed to serve requests.
     */
    public const array DEFAULT_EXCLUDES = [
        'Neutrino\\Test\\',
        'Neutrino\\Debug\\',
        'Neutrino\\Database\\Cli\\',
        'Neutrino\\Database\\Migrations\\',
        'Neutrino\\Database\\Schema\\',
        'Neutrino\\Foundation\\Cli\\Tasks\\',
        'Neutrino\\Foundation\\Optimize\\',
    ];

    /**
     * @param string       $basePath   Application root.
     * @param string       $vendorDir  Composer vendor directory.
     * @param list<string> $namespaces Namespace prefixes to preload.
     * @param list<string> $paths      Directories whose classes are preloaded (default: <basePath>/app).
     * @param list<string> $excludes   Namespace prefixes never preloaded.
     */
    public function __construct(
        private readonly string $basePath,
        private readonly string $vendorDir,
        private readonly array $namespaces = ['Neutrino\\'],
        private readonly ?array $paths = null,
        private readonly array $excludes = self::DEFAULT_EXCLUDES,
    ) {}

    public function generate(): PreloadResult
    {
        $preloaded = [];
        $skipped = [];

        foreach ($this->candidates() as $class) {
            $error = self::load($class);

            if ($error === null) {
                $preloaded[] = $class;
            } else {
                $skipped[$class] = $error;
            }
        }

        $output = $this->basePath . self::OUTPUT;

        AtomicFile::write($output, $this->render($output, $preloaded));

        return new PreloadResult($output, $preloaded, $skipped);
    }

    /**
     * Removes the generated script, if any.
     */
    public static function clear(string $basePath): void
    {
        $file = $basePath . self::OUTPUT;

        if (is_file($file)) {
            unlink($file);
        }
    }

    /**
     * @return list<string>
     */
    private function candidates(): array
    {
        $classmapFile = $this->vendorDir . '/composer/autoload_classmap.php';

        if (!is_file($classmapFile)) {
            throw new RuntimeException(sprintf(
                'Composer classmap not found in "%s". Run `composer dump-autoload --classmap-authoritative` first.',
                $classmapFile,
            ));
        }

        /** @var array<string, string> $classmap */
        $classmap = require $classmapFile;

        $paths = array_map(
            static fn(string $path): string => rtrim(Path::normalize($path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
            $this->paths ?? [$this->basePath . '/app'],
        );

        $candidates = [];

        foreach ($classmap as $class => $file) {
            if ($this->isExcluded($class)) {
                continue;
            }

            if ($this->matchesNamespace($class) || $this->isInPaths(Path::normalize($file), $paths)) {
                $candidates[] = $class;
            }
        }

        sort($candidates);

        return $candidates;
    }

    private function matchesNamespace(string $class): bool
    {
        foreach ($this->namespaces as $namespace) {
            if (str_starts_with($class, $namespace)) {
                return true;
            }
        }

        return false;
    }

    private function isExcluded(string $class): bool
    {
        foreach ($this->excludes as $namespace) {
            if (str_starts_with($class, $namespace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $paths
     */
    private function isInPaths(string $file, array $paths): bool
    {
        foreach ($paths as $path) {
            if (str_starts_with($file, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Loads a class-like, and returns why it cannot be loaded, if so.
     */
    private static function load(string $class): ?string
    {
        try {
            if (class_exists($class) || interface_exists($class) || trait_exists($class) || enum_exists($class)) {
                return null;
            }

            return 'not found by the autoloader';
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param list<string> $classes
     */
    private function render(string $output, array $classes): string
    {
        $autoload = Path::findRelative(dirname($output), $this->vendorDir . '/autoload.php');
        $list = '';

        foreach ($classes as $class) {
            $list .= '    ' . var_export($class, true) . ",\n";
        }

        return <<<PHP
            <?php

            // Generated by the `optimize` task. Do not edit.
            //
            // php.ini:
            //   opcache.preload = {$output}
            //   opcache.preload_user = <the PHP-FPM user>

            declare(strict_types=1);

            require __DIR__ . '/{$autoload}';

            foreach ([
            {$list}] as \$class) {
                class_exists(\$class) || interface_exists(\$class) || trait_exists(\$class) || enum_exists(\$class);
            }

            PHP;
    }
}

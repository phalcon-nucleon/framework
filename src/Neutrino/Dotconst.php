<?php

declare(strict_types=1);

namespace Neutrino;

use Neutrino\Dotconst\Exception\RuntimeException;
use Neutrino\Dotconst\Extensions\Extension;
use Neutrino\Dotconst\Extensions\PhpConst;
use Neutrino\Dotconst\Extensions\PhpDir;
use Neutrino\Dotconst\Extensions\PhpEnv;
use Neutrino\Dotconst\Loader;

/**
 * Defines the application constants from `.const.ini` and `.const.{APP_ENV}.ini`.
 */
final class Dotconst
{
    /**
     * Extensions, by class. Instantiated on first use.
     *
     * @var array<class-string<Extension>, class-string<Extension>|Extension>
     */
    private static array $extensions = [
        PhpDir::class   => PhpDir::class,
        PhpEnv::class   => PhpEnv::class,
        PhpConst::class => PhpConst::class,
    ];

    /**
     * @param class-string<Extension> $extension
     */
    public static function addExtension(string $extension): void
    {
        self::$extensions[$extension] = $extension;
    }

    /**
     * @return array<class-string<Extension>, Extension>
     */
    public static function getExtensions(): array
    {
        foreach (self::$extensions as $extension => $instance) {
            if (is_string($instance)) {
                self::$extensions[$extension] = new $instance();
            }
        }

        /** @var array<class-string<Extension>, Extension> */
        return self::$extensions;
    }

    /**
     * Loads the application constants from `.const.ini` and `.const.{env}.ini`, {env} being the `[APP] env` value.
     *
     * When `$compilePath` contains a compiled `consts.php`, it is loaded instead and the ini files are not read.
     *
     * @param string      $path        Directory of the `.const.ini` files
     * @param string|null $compilePath Directory of the compiled file
     *
     * @throws RuntimeException When a constant is already defined
     * @throws Dotconst\Exception\InvalidFileException
     */
    public static function load(string $path, ?string $compilePath = null): void
    {
        if ($compilePath !== null && $compilePath !== '' && Loader::fromCompile($compilePath)) {
            return;
        }

        foreach (Loader::fromFiles($path) as $const => $value) {
            if (defined($const)) {
                throw new RuntimeException('Constant ' . $const . ' already defined');
            }
            define($const, $value);
        }
    }
}

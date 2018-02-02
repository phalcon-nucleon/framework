<?php

namespace Neutrino;

use Neutrino\Dotconst\Exception\RuntimeException;
use Neutrino\Dotconst\Extensions\PhpConst;
use Neutrino\Dotconst\Extensions\PhpDir;
use Neutrino\Dotconst\Extensions\PhpEnv;
use Neutrino\Dotconst\Loader;

/**
 * Class Dotconst
 *
 * @package Neutrino
 */
class Dotconst
{

    private static $extensions = [
      PhpDir::class => PhpDir::class,
      PhpEnv::class => PhpEnv::class,
      PhpConst::class => PhpConst::class,
    ];

    /**
     * @param string $extension
     *
     * @return mixed
     */
    public static function addExtension($extension)
    {
        return self::$extensions[$extension] = $extension;
    }

    /**
     * @return \Neutrino\Dotconst\Extensions\Extension[]
     */
    public static function getExtensions()
    {
        foreach (self::$extensions as $extension => $class) {
            if (is_string($class)) {
                self::$extensions[$extension] = new $class;
            }
        }

        return self::$extensions;
    }

    /**
     * Loads application constants from .const.ini & .const.{env}.ini files
     * {env} is matched by [APP_ENV] constant
     *
     * If a "consts.php" file is present in the $compilePath, .consts.ini & .const.{env}.ini was not loaded & parsed,
     * the compiled files is automatically loaded.
     *
     * @param string $path Path to ".const.ini" files
     * @param string $compilePath
     *
     * @throws \RuntimeException
     */
    public static function load($path, $compilePath = null)
    {
        if (!$compilePath || !Loader::fromCompile($compilePath)) {
            foreach (Loader::fromFiles($path) as $const => $value) {
                if (defined($const)) {
                    throw new RuntimeException('Constant ' . $const . ' already defined');
                }
                define($const, $value);
            };
        }
    }
}

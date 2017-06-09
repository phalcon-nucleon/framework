<?php

namespace Neutrino\Dotconst;

use Neutrino\Dotconst\Exception\Exception;
use Neutrino\Dotconst\Exception\InvalidFileException;
use Neutrino\Support\Str;

/**
 * Class Loader
 *
 * @package Neutrino\Dotconst
 */
class Loader
{
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
     * @throws \Neutrino\Dotconst\Exception\Exception
     */
    public static function load($path, $compilePath = null)
    {
        if (!$compilePath || !self::fromCompile($compilePath)) {
            foreach (self::fromFiles($path) as $const => $value) {
                if (defined($const)) {
                    throw new Exception('Constant ' . $const . ' already defined');
                }
                define($const, $value);
            };
        }
    }

    /**
     * Load Compiled contants file
     *
     * @param string $path
     *
     * @return bool
     */
    public static function fromCompile($path)
    {
        if (file_exists($compilePath = $path . '/consts.php')) {
            require $compilePath;

            return true;
        }

        return false;
    }

    /**
     * Load & parse .const.ini & .const.{env}.ini files
     *
     * {env} is matched by [APP_ENV] Parameter
     *
     * @param string $path
     *
     * @return array
     */
    public static function fromFiles($path)
    {
        $pathEnv = $path . DIRECTORY_SEPARATOR . '.const';

        if (!file_exists($pathEnv . '.ini')) {
            return [];
        }

        $config = self::parse($pathEnv . '.ini');

        $definable = self::definable($config);

        if (isset($definable['APP_ENV']) && ($env = $definable['APP_ENV']) && !empty($env) && file_exists($pathEnv . '.' . $env . '.ini')) {
            foreach (self::parse($pathEnv . '.' . $env . '.ini') as $section => $value) {
                if (isset($config[$section]) && is_array($value)) {
                    $config[$section] = array_merge($config[$section], $value);
                } else {
                    $config[$section] = $value;
                }
            }

            $definable = self::definable($config);
        }

        return $definable;
    }

    /**
     * @param $file
     *
     * @return array
     * @throws \Neutrino\Dotconst\Exception\InvalidFileException
     */
    private static function parse($file)
    {
        if (($config = parse_ini_file($file, true, INI_SCANNER_TYPED)) === false) {
            throw new InvalidFileException('Failed parse file : ' . $file);
        }

        return self::dynamize(self::upperKeys($config), dirname($file));
    }

    private static function dynamize($config, $dir)
    {
        array_walk_recursive($config, function (&$value) use ($dir) {
            $value = self::variabilize('php/const:([\w:\\\\]+)', $value, function ($match) {
                return constant($match[1]);
            });
            $value = self::variabilize('php/env:(\w+)(?::(\w+))?', $value, function ($match) {
                $value = getenv($match[1]);

                return $value === false ? (isset($match[2]) ? $match[2] : null) : $value;
            });
            $value = self::variabilize('php/dir(?::(/[\w\-. ]+))?', $value, function ($match) use ($dir) {
                return Str::normalizePath($dir . (isset($match[1]) ? $match[1] : ''));
            });
        });

        array_walk_recursive($config, function (&$value) use (&$config) {
            $value = self::variabilize('\{(\w+)\}', $value, function ($match) use ($config) {
                $key = strtoupper($match[1]);

                return isset($config[$key]) ? $config[$key] : $match[1];
            });
        });

        return $config;
    }

    /**
     * @param $array
     *
     * @return array
     */
    private static function upperKeys($array)
    {
        return array_map(function ($item) {
            if (is_array($item)) {
                $item = self::upperKeys($item);
            }

            return $item;
        }, array_change_key_case($array, CASE_UPPER));
    }

    /**
     * @param $pattern
     * @param $str
     * @param $by
     *
     * @return mixed
     */
    private static function variabilize($pattern, $str, $by)
    {
        if (preg_match('#^@' . $pattern . '@?#', $str, $match)) {
            $str = preg_replace('#^@' . $pattern . '@?#', $by($match), $str);
        }

        return $str;
    }

    /**
     * @param array $config
     *
     * @return array
     */
    private static function definable(array $config)
    {
        $flatten = [];
        foreach ($config as $section => $value) {
            if (is_array($value)) {
                $value = self::definable($value);
                foreach ($value as $k => $v) {
                    $flatten["{$section}_{$k}"] = $v;
                }
            } else {
                $flatten[$section] = $value;
            }
        }

        return $flatten;
    }
}

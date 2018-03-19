<?php

namespace Neutrino\Dotconst;

use Neutrino\Dotconst\Exception\CycleNestedConstException;
use Neutrino\Dotconst\Exception\InvalidFileException;

/**
 * Class IniFile
 *
 * @package Neutrino\Dotconst
 */
class Helper
{

    public static function loadIniFile($file)
    {
        $config = parse_ini_file($file, true, INI_SCANNER_TYPED);

        if ($config === false) {
            throw new InvalidFileException('Failed parse file : ' . $file);
        }

        return array_change_key_case(self::definable($config), CASE_UPPER);
    }

    public static function mergeConfigWithFile($config, $file)
    {
        foreach (self::loadIniFile($file) as $section => $value) {
            if (isset($config[$section]) && is_array($value)) {
                $config[$section] = array_merge($config[$section], $value);
            } else {
                $config[$section] = $value;
            }
        }

        return $config;
    }

    public static function nestedConstSort($nested)
    {
        $stack = 0;

        $sort = function ($a, $b) use ($nested, &$stack, &$sort) {
            if ($stack++ >= 128) {
                throw new CycleNestedConstException();
            }

            if (is_null($a['require']) && is_null($b['require'])) {
                $return = 0;
            } elseif (is_null($a['require'])) {
                $return = -1;
            } elseif (is_null($b['require'])) {
                $return = 1;
            } elseif (isset($nested[$a['require']]) && isset($nested[$b['require']])) {
                $return = $sort($nested[$a['require']], $nested[$b['require']]);
            } elseif (isset($nested[$a['require']]) && !isset($nested[$b['require']])) {
                $return = 1;
            } elseif (!isset($nested[$a['require']]) && isset($nested[$b['require']])) {
                $return = -1;
            } else {
                $return = 0;
            }

            $stack--;

            return $return;
        };

        if (PHP_VERSION_ID < 70000) {
            $stable_uasort = function (&$array, $sort) use (&$stable_uasort) {
                if (count($array) < 2) {
                    return;
                }
                $halfway = count($array) / 2;
                $array1 = array_slice($array, 0, $halfway, true);
                $array2 = array_slice($array, $halfway, null, true);

                $stable_uasort($array1, $sort);
                $stable_uasort($array2, $sort);
                if (call_user_func($sort, end($array1),
                    reset($array2)) < 1) {
                    $array = $array1 + $array2;
                    return;
                }
                $array = [];
                reset($array1);
                reset($array2);
                while (current($array1) && current($array2)) {
                    if (call_user_func($sort, current($array1),
                        current($array2)) < 1) {
                        $array[key($array1)] = current($array1);
                        next($array1);
                    } else {
                        $array[key($array2)] = current($array2);
                        next($array2);
                    }
                }
                while (current($array1)) {
                    $array[key($array1)] = current($array1);
                    next($array1);
                }
                while (current($array2)) {
                    $array[key($array2)] = current($array2);
                    next($array2);
                }
                return;
            };

            $stable_uasort($nested, $sort);
        } else {
            uasort($nested, $sort);
        }

        return $nested;
    }

    private static function definable($config)
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

    /**
     * @param $path
     *
     * @return string
     */
    public static function normalizePath($path)
    {
        if (empty($path)) {
            return '';
        }

        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

        $parts = explode('/', $path);

        $safe = [];
        foreach ($parts as $idx => $part) {
            if (($idx == 0 && empty($part))) {
                $safe[] = '';
            } elseif (trim($part) == "" || $part == '.') {
            } elseif ('..' == $part) {
                if (null === array_pop($safe) || empty($safe)) {
                    $safe[] = '';
                }
            } else {
                $safe[] = $part;
            }
        }

        if (count($safe) === 1 && $safe[0] === '') {
            return DIRECTORY_SEPARATOR;
        }

        return implode(DIRECTORY_SEPARATOR, $safe);
    }
}

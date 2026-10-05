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

        uasort($nested, $sort);

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

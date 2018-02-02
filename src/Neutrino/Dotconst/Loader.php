<?php

namespace Neutrino\Dotconst;

use Neutrino\Dotconst;

/**
 * Class Loader
 *
 * @package Neutrino\Dotconst
 */
class Loader
{
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

        $raw = self::loadRaw($path);

        $config = self::parse($raw, $pathEnv . '.ini');

        return $config;
    }

    public static function loadRaw($path)
    {
        $basePath = $path . DIRECTORY_SEPARATOR . '.const';

        $path = $basePath . '.ini';

        if (!file_exists($path)) {
            return [];
        }

        $raw = Helper::loadIniFile($path);

        $config = self::parse($raw, $path);

        if (!empty($config['APP_ENV']) && file_exists($pathEnv = $basePath . '.' . $config['APP_ENV'] . '.ini')) {
            $raw = Helper::mergeConfigWithFile($raw, $pathEnv);
        }

        return $raw;
    }

    /**
     * @param array $config
     * @param string $file
     *
     * @return array
     * @throws \Neutrino\Dotconst\Exception\InvalidFileException
     */
    private static function parse($config, $file)
    {
        return self::dynamize($config, dirname($file));
    }

    private static function dynamize($config, $dir)
    {
        foreach (Dotconst::getExtensions() as $extension) {
            foreach ($config as $const => $value) {
                if ($extension->identify($value)) {
                    $config[$const] = $extension->parse($value, $dir);
                }
            }
        }

        $nested = [];
        foreach ($config as $const => $value) {
            if (preg_match('#^@\{(\w+)\}@?#', $value, $match)) {
                $key = strtoupper($match[1]);

                $value = preg_replace('#^@\{(\w+)\}@?#', '', $value);

                $draw = '';
                $require = null;
                if(isset($config[$key])){
                    $require = $key;
                } else {
                    $draw .= $match[1] ;
                }

                $value = $draw . $value;

                $nested[$const] = ['require' => $require, 'value' => $value];
            }
        }

        $nested = Helper::nestedConstSort($nested);

        foreach ($nested as $const => $value) {
            $v = null;
            if (isset($config[$value['require']])) {
                $v = $config[$value['require']];
            }
            if (!empty($value['value'])) {
                $v .= $value['value'];
            }

            $config[$const] = $v;
        }

        return $config;
    }
}

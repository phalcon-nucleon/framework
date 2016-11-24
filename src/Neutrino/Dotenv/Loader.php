<?php

namespace Neutrino\Dotenv;

use Neutrino\Dotenv\Exception\InvalidFileException;

class Loader
{
    /**
     * Loads environment variables from .env.php to getenv(), $_ENV, and $_SERVER automatically.
     *
     * @param string $path Path to ".env.php" files
     *
     * @return bool
     * @throws \Neutrino\Dotenv\Exception\InvalidFileException
     */
    public static function load($path)
    {
        if (!file_exists($path . DIRECTORY_SEPARATOR . '.env.php')) {
            return false;
        }

        $has_putenv = function_exists('putenv');

        /** @noinspection PhpIncludeInspection */
        $config = require $path . DIRECTORY_SEPARATOR . '.env.php';

        if (isset($config['APP_ENV']) && ($env = $config['APP_ENV']) && !empty($env)) {
            if (file_exists($path . DIRECTORY_SEPARATOR . '.env.' . $env . '.php')) {
                /** @noinspection PhpIncludeInspection */
                $config = array_merge($config, require $path . DIRECTORY_SEPARATOR . '.env.' . $env . '.php');
            }
        }

        foreach ($config as $key => $value) {
            if (!is_string($value)) {
                throw new InvalidFileException('Neutrino\Dotenv support only string value.');
            }

            if ($has_putenv) {
                putenv($key . '=' . $value);
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        return true;
    }
}
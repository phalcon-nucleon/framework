<?php

namespace Neutrino;

class Dotenv
{
    /**
     * Gets the value of an environment variable. Supports boolean, empty and null.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return bool|null|string
     */
    public static function env($key, $default = null)
    {
        $value = self::getenv($key);

        if (is_null($value)) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }

    /**
     * @param $key
     * @return null|string
     */
    private static function getenv($key)
    {
        $value = getenv($key);

        if ($value !== false) {
            return $value;
        }

        return isset($_ENV[$key]) ? $_ENV[$key] : null;
    }
}
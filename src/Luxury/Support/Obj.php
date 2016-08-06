<?php

namespace Luxury\Support;

use Closure;

/**
 * Class Obj
 *
 * @package Luxury\Support
 */
class Obj
{

    /**
     * Fill in data where it's missing.
     *
     * @param  mixed        $target
     * @param  string|array $key
     * @param  mixed        $value
     *
     * @return mixed
     */
    public static function fill(&$target, $key, $value)
    {
        return self::set($target, $key, $value, false);
    }

    /**
     * @param      $object
     * @param      $property
     * @param null $default
     *
     * @return null
     */
    public static function read($object, $property, $default = null)
    {
        if (is_null($object)) {
            return self::value($default);
        }

        if (isset($object->$property) || property_exists($object, $property)) {
            return $object->$property;
        }

        return self::value($default);
    }

    /**
     * @param      $object
     * @param      $property
     * @param null $default
     *
     * @return null
     */
    public static function fetch($object, $property, $default = null)
    {
        if (is_null($object)) {
            return self::value($default);
        }

        if (isset($object->$property)) {
            return $object->$property;
        }

        return self::value($default);
    }

    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed        $target
     * @param  string|array $key
     * @param  mixed        $default
     *
     * @return mixed
     */
    public static function get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (($segment = array_shift($key)) !== null) {
            if ($segment === '*') {
                if (!Arr::accessible($target)) {
                    return self::value($default);
                }

                $result = Arr::pluck($target, $key);

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            if (Arr::accessible($target)) {
                if (!Arr::exists($target, $segment)) {
                    return self::value($default);
                }

                $target = $target[$segment];
            } elseif (is_object($target)) {
                if (!isset($target->{$segment})) {
                    return self::value($default);
                }

                $target = $target->{$segment};
            } else {
                return self::value($default);
            }
        }

        return $target;
    }

    /**
     * Set an item on an array or object using dot notation.
     *
     * @param  mixed        $target
     * @param  string|array $key
     * @param  mixed        $value
     * @param  bool         $overwrite
     *
     * @return mixed
     */
    public static function set(&$target, $key, $value, $overwrite = true)
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        if (($segment = array_shift($segments)) === '*') {
            if (!Arr::accessible($target)) {
                $target = [];
            }

            if ($segments) {
                foreach ($target as &$inner) {
                    self::set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (Arr::accessible($target)) {
            if ($segments) {
                if (!Arr::exists($target, $segment)) {
                    $target[$segment] = [];
                }

                self::set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || !Arr::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (!isset($target->{$segment})) {
                    $target->{$segment} = [];
                }

                self::set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || !isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        }

        return $target;
    }

    /**
     * Return the default value of the given value.
     *
     * @param  mixed $value
     *
     * @return mixed
     */
    public static function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

<?php

namespace Neutrino\Cli\Output;

/**
 * Class Decorate
 *
 * @package Neutrino\Cli\Output
 *
 * @author  Fabien Potencier <fabien@symfony.com>
 * @see     https://github.com/symfony/console/blob/3.0/Formatter/OutputFormatterStyle.php
 *
 * Transformed from symfony/console.
 */
class Decorate
{
    private static $availableForegroundColors = [
        'black'   => ['set' => 30, 'unset' => 39],
        'red'     => ['set' => 31, 'unset' => 39],
        'green'   => ['set' => 32, 'unset' => 39],
        'yellow'  => ['set' => 33, 'unset' => 39],
        'blue'    => ['set' => 34, 'unset' => 39],
        'magenta' => ['set' => 35, 'unset' => 39],
        'cyan'    => ['set' => 36, 'unset' => 39],
        'white'   => ['set' => 37, 'unset' => 39],
        'default' => ['set' => 39, 'unset' => 39],
    ];

    private static $availableBackgroundColors = [
        'black'   => ['set' => 40, 'unset' => 49],
        'red'     => ['set' => 41, 'unset' => 49],
        'green'   => ['set' => 42, 'unset' => 49],
        'yellow'  => ['set' => 43, 'unset' => 49],
        'blue'    => ['set' => 44, 'unset' => 49],
        'magenta' => ['set' => 45, 'unset' => 49],
        'cyan'    => ['set' => 46, 'unset' => 49],
        'white'   => ['set' => 47, 'unset' => 49],
        'default' => ['set' => 49, 'unset' => 49],
    ];

    private static $availableOptions = [
        'bold'       => ['set' => 1, 'unset' => 22],
        'underscore' => ['set' => 4, 'unset' => 24],
        'blink'      => ['set' => 5, 'unset' => 25],
        'reverse'    => ['set' => 7, 'unset' => 27],
        'conceal'    => ['set' => 8, 'unset' => 28],
    ];

    private static $hasColorSupport;

    /**
     * Check if console has color support
     *
     * @return bool
     */
    private static function hasColorSupport()
    {
        if (isset(self::$hasColorSupport)) {
            return self::$hasColorSupport;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            return self::$hasColorSupport =
                (10 == PHP_WINDOWS_VERSION_MAJOR && PHP_WINDOWS_VERSION_BUILD >= 10586)
                || false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM');
        }

        return self::$hasColorSupport = function_exists('posix_isatty') && @posix_isatty(STDOUT);
    }

    /**
     * Force color support.
     *
     * @param bool $support
     */
    public static function setColorSupport($support)
    {
        self::$hasColorSupport = $support;
    }

    /**
     * Applies the style to a given text.
     *
     * @param string $text The text to style=
     * @param null   $foreground
     * @param null   $background
     * @param array  $options
     *
     * @return string
     */
    public static function apply($text, $foreground = null, $background = null, array $options = [])
    {
        if (!self::hasColorSupport()) {
            return $text;
        }

        $setCodes   = [];
        $unsetCodes = [];

        if (null !== $foreground) {
            $setCodes[]   = self::$availableForegroundColors[$foreground]['set'];
            $unsetCodes[] = self::$availableForegroundColors[$foreground]['unset'];
        }
        if (null !== $background) {
            $setCodes[]   = self::$availableBackgroundColors[$background]['set'];
            $unsetCodes[] = self::$availableBackgroundColors[$background]['unset'];
        }
        if (!empty($options)) {
            foreach ($options as $option) {
                $setCodes[]   = self::$availableOptions[$option]['set'];
                $unsetCodes[] = self::$availableOptions[$option]['unset'];
            }
        }

        if (0 === count($setCodes)) {
            return $text;
        }

        return sprintf("\033[%sm%s\033[%sm", implode(';', $setCodes), $text, implode(';', $unsetCodes));
    }

    /**
     * Decorate a string as information output.
     *
     * @param string $str
     *
     * @return string
     */
    public static function info($str)
    {
        return self::apply($str, 'green');
    }

    /**
     * Decorate a string as notice output.
     *
     * @param string $str
     *
     * @return string
     */
    public static function notice($str)
    {
        return self::apply($str, 'yellow');
    }

    /**
     * Decorate a string as warning output.
     *
     * @param string $str
     *
     * @return string
     */
    public static function warn($str)
    {
        return self::apply($str, 'yellow', null, ['reverse']);
    }

    /**
     * Decorate a string as error output.
     *
     * @param string $str
     *
     * @return string
     */
    public static function error($str)
    {
        return self::apply($str, 'black', 'red');
    }

    /**
     * Decorate a string as question output.
     *
     * @param string $str
     *
     * @return string
     */
    public static function question($str)
    {
        return self::apply($str, 'black', 'cyan');
    }
}

<?php

namespace Neutrino\Support\Facades;

use Neutrino\Constants\Services;

/**
 * Class Log
 *
 *  @package Neutrino\Support\Facades
 *
 * @method static void emergency($message) Log an emergency message.
 * @method static void alert($message) Log an alert message.
 * @method static void critical($message) Log an critical message.
 * @method static void error($message) Log an error message.
 * @method static void warning($message) Log an warning message.
 * @method static void notice($message) Log an notice message.
 * @method static void info($message) Log an info message.
 * @method static void debug($message) Log an debug message.
 * @method static void log($message, $level) Log an message on the given level.
 */
class Log extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Services::LOGGER;
    }
}

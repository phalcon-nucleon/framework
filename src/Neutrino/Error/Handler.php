<?php
/*
  +------------------------------------------------------------------------+
  | Phalcon Framework                                                      |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2016 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file docs/LICENSE.txt.                        |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  |          Nikita Vershinin <endeveit@gmail.com>                         |
  |          Serghei Iakovlev <serghei@phalconphp.com>                     |
  +------------------------------------------------------------------------+
*/
namespace Neutrino\Error;

use Neutrino\Error\Writer\Phplog;
use Neutrino\Error\Writer\Writable;

/**
 * Class Handler
 *
 * @package Phalcon\Error
 */
class Handler
{
    /** @var Writable[] */
    private static $writers = [Phplog::class => null];

    /**
     * @param array $writers
     */
    public static function setWriters(array $writers)
    {
        self::$writers = array_fill_keys($writers, null);
    }

    /**
     * Registers itself as error and exception handler.
     *
     * @return void
     */
    public static function register()
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            self::handleError($errno, $errstr, $errfile, $errline);
        });
        set_exception_handler(function ($e) {
            self::handleException($e);
        });
        register_shutdown_function(function () {
            // Handle Fatal error
            $error = error_get_last();
            if (isset($error['type']) && $error['type'] === E_ERROR) {
                self::handleError($error['type'], $error['message'], $error['file'], $error['line']);
            }
        });
    }

    /**
     * Handle an php Error
     *
     * @param int    $errno
     * @param string $errstr
     * @param string $errfile
     * @param int    $errline
     */
    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        if (!($errno & error_reporting())) {
            return;
        }

        static::handle(Error::fromError($errno, $errstr, $errfile, $errline));
    }

    /**
     * Handle an Exception non catched
     *
     * @param \Error|\Exception|\Throwable $e
     */
    public static function handleException($e)
    {
        static::handle(Error::fromException($e));
    }

    /**
     * Logs the error and dispatches an error controller.
     *
     * @param  \Neutrino\Error\Error $error
     */
    public static function handle(Error $error)
    {
        foreach (self::$writers as $class => $writer) {
            if (is_null($writer)) {
                self::$writers[$class] = $writer = new $class();
            }
            $writer->handle($error);
        }
    }
}

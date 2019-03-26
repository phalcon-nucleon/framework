<?php

namespace Neutrino\Debug\Exceptions;

use Exception;
use Throwable;

class Handler
{
    /** @var string */
    private static $handlerClass;

    /**
     * @param string $handlerClass
     */
    final public static function register($handlerClass)
    {
        if (isset(self::$handlerClass)) {
            throw new \RuntimeException(__CLASS__ . ' already registered');
        }

        self::$handlerClass = $handlerClass;

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if ($errno & error_reporting()) {
                self::handle(Helper::errorToThrowable($errno, $errstr, $errfile, $errline));
            }
        });
        set_exception_handler(function ($e) {
            self::handle($e);
        });
        register_shutdown_function(function () {
            // Handle Fatal error
            $error = error_get_last();
            if (isset($error['type']) && $error['type'] === E_ERROR) {
                self::handle(Helper::errorToThrowable(
                    $error['type'],
                    $error['message'],
                    $error['file'],
                    $error['line']
                ));
            }
        });
    }

    /**
     * @param $throwable
     *
     * @return void
     */
    private static function handle($throwable)
    {
        try {
            $handlerClass = self::$handlerClass;

            /** @var \Neutrino\Debug\Exceptions\ExceptionHandlerInterface $handler */
            $handler = new $handlerClass;

            $handler->handle($throwable);
        } catch (Exception $e) {
        } catch (Throwable $e) {
        }
    }
}

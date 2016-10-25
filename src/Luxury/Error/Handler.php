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
namespace Luxury\Error;

use Luxury\Constants\Env;
use Luxury\Constants\Services;
use Phalcon\Di;
use Phalcon\Http\Response;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\File as FileLogger;
use Phalcon\Logger\Formatter;
use Phalcon\Logger\Formatter\Line as FormatterLine;

/**
 * Class Handler
 *
 * @package Phalcon\Error
 */
class Handler
{
    /**
     * Registers itself as error and exception handler.
     *
     * @return void
     */
    public static function register()
    {
        switch (APP_ENV) {
            case Env::TEST:
            case Env::DEVELOPMENT:
            case Env::STAGING:
                ini_set('display_errors', 1);
                error_reporting(-1);
                break;
            case Env::PRODUCTION:
            default:
                ini_set('display_errors', 0);
                error_reporting(0);
                break;
        }

        set_error_handler(
            function ($errno, $errstr, $errfile, $errline) {
                if (!($errno & error_reporting())) {
                    return;
                }

                $options = [
                    'type'    => $errno,
                    'message' => $errstr,
                    'file'    => $errfile,
                    'line'    => $errline,
                    'isError' => true,
                ];

                static::handle(new Error($options));
            }
        );

        set_exception_handler(
            function ($e) {
                /** @var \Error|\Exception $e */
                $options = [
                    'type'        => $e->getCode(),
                    'message'     => $e->getMessage(),
                    'file'        => $e->getFile(),
                    'line'        => $e->getLine(),
                    'isException' => true,
                    'exception'   => $e,
                ];

                static::handle(new Error($options));
            }
        );

        register_shutdown_function(
            function () {
                if (!is_null($options = error_get_last())) {
                    static::handle(new Error($options));
                }
            }
        );
    }

    /**
     * Logs the error and dispatches an error controller.
     *
     * @param  \Luxury\Error\Error $error
     *
     * @return null|Response
     */
    public static function handle(Error $error)
    {
        $di = Di::getDefault();

        /* @var \Phalcon\Config $config */
        $config = $di->getShared(Services::CONFIG)->error;

        /* @var \Phalcon\Logger\Adapter $logger */
        $logger = $di->getShared(Services::LOGGER);

        $type    = static::getErrorType($error->type);
        $message = "$type: {$error->message} in {$error->file} on line {$error->line}";

        if (isset($config['formatter'])) {
            $formatter = null;

            if ($config['formatter'] instanceof Formatter) {
                $formatter = $config['formatter'];
            } elseif (is_array($config['formatter'])) {
                $formatterOpts = $config['formatter'];
                $format        = null;
                $dateFormat    = null;

                if (isset($formatter['format'])) {
                    $format = $formatter['format'];
                }

                if (isset($formatterOpts['dateFormat'])) {
                    $dateFormat = $formatterOpts['dateFormat'];
                } elseif (isset($formatterOpts['date_format'])) {
                    $dateFormat = $formatterOpts['date_format'];
                } elseif (isset($formatterOpts['date'])) {
                    $dateFormat = $formatterOpts['date'];
                }

                $formatter = new FormatterLine($format, $dateFormat);
            }

            if ($formatter) {
                $logger->setFormatter($formatter);
            }
        }

        $logger->log(static::getLogType($error->type), $message);

        switch ($error->type) {
            case E_WARNING:
            case E_NOTICE:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
            case E_USER_NOTICE:
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
            case E_ALL:
                break;
            case 0:
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                if ($di->has(Services::VIEW)) {
                    /* @var \Phalcon\Mvc\Dispatcher $dispatcher */
                    $dispatcher = $di->getShared(Services::DISPATCHER);
                    /* @var \Phalcon\Mvc\View $view */
                    $view = $di->getShared(Services::VIEW);
                    /* @var \Phalcon\Http\Response $response */
                    $response = $di->getShared(Services::RESPONSE);

                    $dispatcher->setNamespaceName($config['namespace']);
                    $dispatcher->setControllerName($config['controller']);
                    $dispatcher->setActionName($config['action']);
                    $dispatcher->setParams(['error' => $error]);

                    $view->start();
                    $dispatcher->dispatch();
                    $view->render(
                        $config['controller'],
                        $config['action'],
                        $dispatcher->getParams()
                    );
                    $view->finish();

                    return $response->setContent($view->getContent())->send();
                } else {
                    echo $message;
                }
        }

        return null;
    }

    /**
     * Maps error code to a string.
     *
     * @param  integer $code
     *
     * @return string
     */
    public static function getErrorType($code)
    {
        switch ($code) {
            case 0:
                return 'Uncaught exception';
            case E_ERROR:
                return 'E_ERROR';
            case E_WARNING:
                return 'E_WARNING';
            case E_PARSE:
                return 'E_PARSE';
            case E_NOTICE:
                return 'E_NOTICE';
            case E_CORE_ERROR:
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
                return 'E_USER_ERROR';
            case E_USER_WARNING:
                return 'E_USER_WARNING';
            case E_USER_NOTICE:
                return 'E_USER_NOTICE';
            case E_STRICT:
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
        }

        return (string)$code;
    }

    /**
     * Maps error code to a log type.
     *
     * @param  integer $code
     *
     * @return integer
     */
    public static function getLogType($code)
    {
        switch ($code) {
            case E_PARSE:
                return Logger::CRITICAL;
            case E_COMPILE_ERROR:
            case E_CORE_ERROR:
            case E_ERROR:
                return Logger::EMERGENCY;
            case E_RECOVERABLE_ERROR:
            case E_USER_ERROR:
                return Logger::ERROR;
            case E_WARNING:
            case E_USER_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
                return Logger::WARNING;
            case E_NOTICE:
            case E_USER_NOTICE:
                return Logger::NOTICE;
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return Logger::INFO;
        }

        return Logger::ERROR;
    }
}

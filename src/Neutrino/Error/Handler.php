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

use Neutrino\Cli\Output\Block;
use Neutrino\Constants\Services;
use Neutrino\Support\Arr;
use Phalcon\Di;
use Phalcon\Http\Response;
use Phalcon\Logger;
use Phalcon\Logger\Formatter;
use Phalcon\Logger\Formatter\Line as FormatterLine;

/**
 * Class Handler
 *
 * @package Phalcon\Error
 */
class Handler
{
    const OUTPUT_PHPLOG = 1;
    const OUTPUT_LOGGER = 2;
    const OUTPUT_VIEW   = 4;
    const OUTPUT_JSON   = 8;
    const OUTPUT_CLI    = 16;

    private static $outputLvl = self::OUTPUT_PHPLOG;

    public static function setOutputLvl($lvl)
    {
        self::$outputLvl = $lvl;
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
            if (!is_null($e = error_get_last())) {
                static::handle(new Error($e));
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

        static::handle(new Error([
            'type'    => $errno,
            'message' => $errstr,
            'file'    => $errfile,
            'line'    => $errline,
            'isError' => true,
        ]));
    }

    /**
     * Handle an Exception non catched
     *
     * @param \Error|\Exception|\Throwable $e
     */
    public static function handleException($e)
    {
        static::handle(new Error([
            'type'        => $e->getCode(),
            'message'     => $e->getMessage(),
            'file'        => $e->getFile(),
            'line'        => $e->getLine(),
            'isException' => true,
            'exception'   => $e,
        ]));
    }

    /**
     * Logs the error and dispatches an error controller.
     *
     * @param  \Neutrino\Error\Error $error
     */
    public static function handle(Error $error)
    {
        if (self::OUTPUT_PHPLOG & self::$outputLvl) {
            self::outputErrorLog($error);
        }

        if (self::OUTPUT_LOGGER & self::$outputLvl) {
            self::outputLogger($error);
        }

        $type = $error->type;
        if ($type == 0 ||
            $type == E_ERROR ||
            $type == E_PARSE ||
            $type == E_CORE_ERROR ||
            $type == E_COMPILE_ERROR ||
            $type == E_USER_ERROR ||
            $type == E_RECOVERABLE_ERROR
        ) {
            if (self::OUTPUT_VIEW & self::$outputLvl) {
                self::outputView($error);
            }
            if (self::OUTPUT_JSON & self::$outputLvl) {
                self::outputJson($error);
            }
            if (self::OUTPUT_CLI & self::$outputLvl) {
                self::outputCli($error);
            }
        }
    }

    private static function outputErrorLog(Error $error)
    {
        error_log(self::format($error), 0);
    }

    private static function outputLogger(Error $error)
    {
        $di = Di::getDefault();
        if ($di && $di->has(Services::LOGGER)) {
            /* @var \Phalcon\Logger\Adapter $logger */
            /* @var \Phalcon\Config $config */
            $logger = $di->getShared(Services::LOGGER);

            $config = [];
            if ($di->has(Services::CONFIG)) {
                $config = $di->getShared(Services::CONFIG);
            }

            if (Arr::has($config, 'error.formatter')) {
                $configFormat = Arr::get($config, 'error.formatter');
                $formatter    = null;

                if ($configFormat instanceof Formatter) {
                    $formatter = $configFormat;
                } elseif (is_array($configFormat)) {
                    $formatterOpts = $configFormat;
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

            $logger->log(static::getLogType($error->type), self::format($error, true, true));
        }
    }

    private static function outputView(Error $e)
    {
        $di     = Di::getDefault();
        $config = $di->getShared(Services::CONFIG);

        if ($di) {
            if ($di->has(Services::VIEW)) {
                /* @var \Phalcon\Mvc\View $view */
                $view = $di->getShared(Services::VIEW);
                $view->start();
                if (Arr::has($config, 'error.dispatcher.namespace')
                    && Arr::has($config, 'error.dispatcher.controller')
                    && Arr::has($config, 'error.dispatcher.action')
                ) {
                    /* @var \Phalcon\Mvc\Dispatcher $dispatcher */
                    $dispatcher = $di->getShared(Services::DISPATCHER);
                    $dispatcher->setNamespaceName(Arr::get($config, 'error.dispatcher.namespace'));
                    $dispatcher->setControllerName(Arr::get($config, 'error.dispatcher.controller'));
                    $dispatcher->setActionName(Arr::get($config, 'error.dispatcher.action'));
                    $dispatcher->setParams(['error' => $e]);
                    $dispatcher->dispatch();
                } elseif (Arr::has($config, 'error.view.path')
                    && Arr::has($config, 'error.view.file')
                ) {
                    $view->render(
                        Arr::get($config, 'error.view.path'),
                        Arr::get($config, 'error.view.file'),
                        ['error' => $e]
                    );
                } else {
                    $view->setContent(self::format($e, false, true));
                }
                $view->finish();

                if ($di->has(Services::RESPONSE) && ($response =
                        $di->getShared(Services::RESPONSE)) instanceof Response && !$response->isSent()
                ) {
                    $response
                        ->setStatusCode(500)
                        ->setContent($view->getContent())
                        ->send();

                    return;
                } else {
                    echo $view->getContent();

                    return;
                }
            }
        }

        echo self::format($e, false, true);
    }

    private static function outputJson(Error $error)
    {
        $di = Di::getDefault();

        if ($di
            && $di->has(Services::RESPONSE)
            && ($response = $di->getShared(Services::RESPONSE)) instanceof Response && !$response->isSent()
        ) {
            $response
                ->setStatusCode(500)
                ->setContent(json_encode($error))
                ->send();
        } else {
            echo json_encode($error);
        }
    }

    private static function outputCli(Error $error)
    {
        $di = Di::getDefault();

        if ($di && $di->has(Services\Cli::OUTPUT)) {
            $output = $di->getShared(Services\Cli::OUTPUT);

            $block = new Block($output, 'warn', ['padding' => 4]);

            $block->draw(explode("\n", self::format($error, false, true)));
        } else {
            echo self::format($error, false, true);
        }
    }

    private static function format(Error $error, $full = false, $verbose = false)
    {
        $type = static::getErrorType($error->type);

        if ($full || APP_DEBUG) {
            if ($verbose) {
                $lines[] = $type
                    . ($error->isException ? ' : ' . get_class($error->exception) : ' : Error')
                    . '[' . (empty($error->type) ? 0 : ' : ' . $error->type) . ']'
                    . (empty($error->message) ? '' : ' : ' . $error->message)
                    . ' in ' . str_replace(DIRECTORY_SEPARATOR, '/', str_replace(BASE_PATH, '{base_path}', $error->file))
                    . ' on line ' . $error->line;

                if ($error->isException) {
                    $lines[] = '';

                    foreach ($error->exception->getTrace() as $idx => $trace) {
                        $row = $id = '#' . $idx . ' ';

                        if (isset($trace['class'])) {
                            $row .= $trace['class'] . '::';
                        }
                        if (isset($trace['function'])) {
                            $row .= $trace['function'];
                        }

                        $args = [];
                        foreach ($trace['args'] as $arg) {
                            switch (gettype($arg)) {
                                case 'array':
                                    if (!empty($arg)) {
                                        $found = [];
                                        foreach ($arg as $item) {
                                            $type = gettype($item);
                                            if ($type == 'object') {
                                                $type = get_class($item);
                                            }
                                            if (!isset($found[$type])) {
                                                $found[$type] = 0;
                                            }

                                            $found[$type]++;
                                        }

                                        asort($found, SORT_DESC);
                                        reset($found);
                                        $key = key($found);
                                        if ($found[$key] == count($arg)) {
                                            $args[] = $key . '[]';
                                        } else {
                                            $args[] = 'Array';
                                        }
                                    } else {
                                        $args[] = 'Array';
                                    }

                                    break;
                                case 'object':
                                    $args[] = get_class($arg);
                                    break;
                                case 'NULL':
                                    $args[] = 'null';
                                    break;
                                case 'unknown type':
                                    $args[] = '?';
                                    break;
                                case 'boolean':
                                case 'integer':
                                case 'double':
                                case 'string':
                                case 'resource':
                                default:
                                    $args[] = $arg;
                                    break;
                            }
                        }
                        $row .= '(' . implode(', ', $args) . ')';

                        $lines[] = $row;

                        $row = str_repeat(' ', strlen($id)) . 'in : ';
                        if (isset($trace['file'])) {
                            $row .= ' ' . str_replace(DIRECTORY_SEPARATOR, '/', str_replace(BASE_PATH, '{base_path}', $trace['file']));
                            if (isset($trace['line'])) {
                                $row .= '(' . $trace['line'] . ')';
                            }
                            $row .= ':';
                        } else {
                            $row .= '[internal function]';
                        }

                        $lines[] = $row;
                    }

                    array_map('trim', $lines);
                }
            } else {
                $lines[] = "$type: {$error->message} in {$error->file} on line {$error->line}";
            }

            $message = implode("\n", $lines);
        } else {
            $message = 'Something went wrong.';
        }

        return $message;
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

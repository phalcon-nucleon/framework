<?php

namespace Neutrino\Debug\Exceptions;

use Exception;
use ErrorException;
use Neutrino\Debug\Reflexion;
use Neutrino\Support\Arr;
use Neutrino\Debug\Exceptions\Errors\CustomErrorException;
use Neutrino\Debug\Exceptions\Errors\DeprecatedErrorException;
use Neutrino\Debug\Exceptions\Errors\FatalErrorException;
use Neutrino\Debug\Exceptions\Errors\NoticeErrorException;
use Neutrino\Debug\Exceptions\Errors\StrictErrorException;
use Neutrino\Debug\Exceptions\Errors\WarningErrorException;
use Phalcon\Logger;
use Throwable;

/**
 * Class Helper
 *
 * @package Neutrino\Debug\Exceptions
 */
class Helper
{
    /**
     * @param int    $errno
     * @param string $errstr
     * @param string $errfile
     * @param int    $errline
     *
     * @return ErrorException
     */
    public static function errorToThrowable($errno, $errstr, $errfile, $errline)
    {
        if (($types = FatalErrorException::TYPES) && isset($types[$errno])) {
            $class = FatalErrorException::class;
        } elseif (($types = WarningErrorException::TYPES) && isset($types[$errno])) {
            $class = WarningErrorException::class;
        } elseif (($types = NoticeErrorException::TYPES) && isset($types[$errno])) {
            $class = NoticeErrorException::class;
        } elseif (($types = DeprecatedErrorException::TYPES) && isset($types[$errno])) {
            $class = DeprecatedErrorException::class;
        } elseif (($types = StrictErrorException::TYPES) && isset($types[$errno])) {
            $class = StrictErrorException::class;
        } else {
            $class = CustomErrorException::class;
        }

        return new $class($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * @param Throwable|Exception $throwable
     *
     * @return bool
     */
    public static function isFateful($throwable)
    {
        return $throwable instanceof FatalErrorException
            || !($throwable instanceof ErrorException)
            || self::isFatefulErrorSeverity($throwable->getSeverity());
    }

    /**
     * @param int $code
     *
     * @return bool
     */
    public static function isFatefulErrorSeverity($code)
    {
        $types = FatalErrorException::TYPES;

        return isset($types[$code]);
    }

    /**
     * Maps error code to a log type.
     *
     * @param Throwable|Exception $throwable
     *
     * @return integer
     */
    public static function logLevel($throwable)
    {
        if ($throwable instanceof FatalErrorException || $throwable instanceof CustomErrorException) {
            return Logger::ERROR;
        } elseif ($throwable instanceof WarningErrorException) {
            return Logger::WARNING;
        } elseif ($throwable instanceof NoticeErrorException) {
            return Logger::NOTICE;
        } elseif ($throwable instanceof DeprecatedErrorException || $throwable instanceof StrictErrorException) {
            return Logger::INFO;
        } elseif ($throwable instanceof ErrorException) {
            return self::logLevel(self::errorToThrowable($throwable->getSeverity(), '', '', 0));
        }

        return Logger::ERROR;
    }

    /**
     * @param Throwable|Exception $throwable
     *
     * @return string
     */
    public static function verbose($throwable)
    {
        return implode("\n", self::verboseThrowable($throwable));
    }

    /**
     * @param array $args
     *
     * @return array
     */
    public static function verboseArgs(array $args)
    {
        $arguments = [];

        foreach ($args as $key => $arg) {
            $arguments[$key] = self::verboseVar($arg);
        }

        return $arguments;
    }

    /**
     * Maps error code to a string.
     *
     * @param int|string $code
     *
     * @return string
     */
    public static function verboseSeverity($code)
    {
        switch ($code) {
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

        return "(unknown error bit $code)";
    }

    /**
     * @param Throwable|Exception $throwable
     *
     * @return string
     */
    public static function verboseType($throwable)
    {
        if ($throwable instanceof FatalErrorException) {
            return 'Fatal error [' . self::verboseSeverity($throwable->getSeverity()) . ']';
        } elseif ($throwable instanceof WarningErrorException) {
            return 'Warning [' . self::verboseSeverity($throwable->getSeverity()) . ']';
        } elseif ($throwable instanceof NoticeErrorException) {
            return 'Notice [' . self::verboseSeverity($throwable->getSeverity()) . ']';
        } elseif ($throwable instanceof DeprecatedErrorException || $throwable instanceof StrictErrorException) {
            return 'Info [' . self::verboseSeverity($throwable->getSeverity()) . ']';
        } elseif ($throwable instanceof CustomErrorException) {
            return 'Custom error [' . $throwable->getSeverity() . ']';
        } elseif ($throwable instanceof ErrorException) {
            return self::verboseType(self::errorToThrowable($throwable->getSeverity(), '', '', 0));
        }

        return 'Uncaught ' . get_class($throwable);
    }

    /**
     * @param mixed $value
     * @param int $lvl
     *
     * @return mixed|string
     */
    public static function verboseVar($value, $lvl = 0)
    {
        switch ($type = gettype($value)) {
            case 'array':
                if (!empty($value) && $lvl === 0) {
                    $found = [];
                    foreach ($value as $item) {
                        $type = gettype($item);
                        if ($type == 'object') {
                            $type = get_class($item);
                        }
                        $found[$type] = true;
                    }

                    $cfound = count($found);
                    $cvalue = count($value);

                    if ($cfound === 1 && !is_scalar($item) && $cvalue < 3
                        || $cfound === 1 && is_scalar($item) && $cvalue < 6
                        || $cfound > 1 && $cvalue < 5) {
                        $str = [];
                        if (Arr::isAssoc($value)) {
                            foreach ($value as $key => $item) {
                                $str[] = var_export($key, true) . " => " . self::verboseVar($item, $lvl + 1);
                            }
                        } else {
                            foreach ($value as $item) {
                                $str[] = self::verboseVar($item, $lvl + 1);
                            }
                        }

                        return 'array(' . implode(', ', $str) . ')';
                    }

                    if (count($found) === 1) {
                        return 'array.<' . $type . '>[' . count($value) . ']';
                    }

                    return 'array[' . count($value) . ']';
                }

                return 'array';
            case 'object':
                $class = explode('\\', get_class($value));
                return 'object(' . array_pop($class) . ')';
            case 'null':
            case 'NULL':
                return 'null';
            case 'unknown type':
                return '?';
            case 'resource':
            case 'resource (closed)':
                return $type;
            case 'string':
                if (defined('BASE_PATH') && $value !== BASE_PATH . '\\') {
                    $value = str_replace(BASE_PATH . '\\', '', $value);
                }
                if (strlen($value) > 20) {
                    return "'" . substr($value, 0, 8) . '...' . substr($value, -8) . '\'[' . strlen($value) . ']';
                }
                return "'" . $value . "'";
            case 'boolean':
            case 'integer':
            case 'double':
            default:
                return var_export($value, true);
        }
    }

    /**
     * @param Throwable|Exception $throwable
     * @param int $pass
     *
     * @return array
     */
    private static function verboseThrowable($throwable, $pass = 0)
    {
        $pass++;

        $lines[] = self::verboseType($throwable);
        if ($throwable instanceof ErrorException) {
            // "error" as exception
            $lines[] = '  Severity : ' . self::verboseSeverity($throwable->getSeverity());
        } else {
            // \Error (php 7)
            // \Throwable (php 7)
            // \Exception
            $lines[] = '  Class : ' . get_class($throwable);
            $lines[] = '  Code : ' . $throwable->getCode();
        }

        $lines[] = '  Message : ' . $throwable->getMessage();

        $lines[] = ' in : ' . str_replace(DIRECTORY_SEPARATOR, '/', $throwable->getFile()) . '(' . $throwable->getLine() . ')';

        if (!($throwable instanceof ErrorException)) {
            $lines[] = '';

            foreach (self::extractTracesToArray($throwable) as $trace) {
                $lines[] = '#' . $trace['id'] . ' ' . $trace['func'];

                $row = str_repeat(' ', strlen($trace['id']) + 2) . 'in : ';
                if (isset($trace['file'])) {
                    $row .= str_replace(DIRECTORY_SEPARATOR, '/', $trace['file']);
                    if (isset($trace['line'])) {
                        $row .= '(' . $trace['line'] . ')';
                    }
                } else {
                    $row .= '[internal function]';
                }

                $lines[] = $row;
            }

            $previous = $throwable->getPrevious();

            if (!is_null($previous)) {
                $lines[] = '';
                $lines[] = '# Previous exception : ' . $pass;
                $lines[] = '';

                $lines = array_merge($lines, self::verboseThrowable($previous, $pass));
            }
        }

        return $lines;
    }

    /**
     * @param Exception|Throwable $exception
     *
     * @return array
     */
    public static function extractTracesToArray($exception)
    {
        $traces = [];

        foreach ($exception->getTrace() as $idx => $trace) {
            $_trace = [];

            $_trace['id'] = $idx;

            $_trace['func'] = '';

            if (isset($trace['class'], $trace['function'])) {
                if (strpos($trace['function'], '{closure}') !== false) {
                    $_trace['func'] = $trace['class'] . '::' . $trace['function'];
                } else {
                    $sep = '->';

                    try {
                        $method = Reflexion::getReflectionMethod($trace['class'], $trace['function']);
                        if ($method->isStatic()) {
                            $sep = '::';
                        }
                    } catch (\ReflectionException $e) {
                    }

                    $_trace['func'] = $trace['class'] . $sep . $trace['function'];
                }
            } elseif (isset($trace['function'])) {
                $_trace['func'] = $trace['function'];
            } elseif (isset($trace['class'])) {
                $_trace['func'] = $trace['class'];
            }

            $_trace['func'] .= '(';
            if (isset($trace['args'])) {
                $_trace['func'] .= implode(', ', self::verboseArgs((array)$trace['args']));
            }
            $_trace['func'] .= ')';

            $_trace['file'] = null;
            $_trace['line'] = null;
            if (isset($trace['file'])) {
                $_trace['file'] = str_replace(DIRECTORY_SEPARATOR, '/', $trace['file']);
                $_trace['where'] = $_trace['file'];

                if (isset($trace['line'])) {
                    $_trace['line'] = $trace['line'];
                    $_trace['where'] .= '(' . $trace['line'] . ')';
                }
            } else {
                $_trace['where'] = '[internal function]';
            }

            $traces[] = $_trace;
        }

        return $traces;
    }
}

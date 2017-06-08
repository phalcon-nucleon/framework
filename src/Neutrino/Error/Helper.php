<?php

namespace Neutrino\Error;

use Phalcon\Logger;

/**
 * Class Helper
 *
 * @package     Neutrino\Error
 */
class Helper
{
    public static function format(Error $error)
    {
        $lines[] = self::getErrorType($error->type)
            . ($error->isException ? ' : ' . get_class($error->exception) . '[' . $error->code . ']' : '')
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
                    $args[] = self::verboseType($arg);
                }
                $row .= '(' . implode(', ', $args) . ')';

                $lines[] = $row;

                $row = str_repeat(' ', strlen($id)) . 'in : ';
                if (isset($trace['file'])) {
                    $row .= str_replace(DIRECTORY_SEPARATOR, '/', str_replace(BASE_PATH, '{base_path}', $trace['file']));
                    if (isset($trace['line'])) {
                        $row .= '(' . $trace['line'] . ')';
                    }
                } else {
                    $row .= '[internal function]';
                }

                $lines[] = $row;
            }
        }

        return implode("\n", $lines);
    }

    public static function verboseType($value)
    {
        switch ($type = gettype($value)) {
            case 'array':
                if (!empty($value)) {
                    $found = [];
                    foreach ($value as $item) {
                        $type = gettype($item);
                        if ($type == 'object') {
                            $type = get_class($item);
                        }
                        $found[$type] = true;
                    }

                    return count($found) === 1 ? $type . '[' . count($value) . ']' : 'Array';
                }

                return 'Array';
            case 'object':
                return get_class($value);
            case 'NULL':
                return 'null';
            case 'unknown type':
                return '?';
            case 'resource':
                return $type;
            case 'string':
                if (strlen($value) > 8) {
                    return 'string(' . strlen($value) . ')';
                }
            case 'boolean':
            case 'integer':
            case 'double':
            default:
                return var_export($value, true);
        }
    }

    /**
     * Maps error code to a string.
     *
     * @param int|string $code
     *
     * @return string
     */
    public static function getErrorType($code)
    {
        switch ($code) {
            case -1:
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
            case -1 : // Exception
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

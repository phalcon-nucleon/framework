<?php

namespace Neutrino\Error;

/**
 * Class Helper
 *
 * @package     Neutrino\Error
 */
class Helper
{
    public static function format(Error $error, $full = false, $verbose = true)
    {
        if ($full || APP_DEBUG) {
            if ($verbose) {
                $head = $error->getErrorType();

                if ($error->isException) {
                    $head .= ' : ' . get_class($error->exception) . '[' . $error->code . ']';
                }

                $head .= (empty($error->message) ? '' : ' : ' . $error->message)
                    . ' in ' . str_replace(DIRECTORY_SEPARATOR, '/', str_replace(BASE_PATH, '{base_path}', $error->file))
                    . ' on line ' . $error->line;

                $lines[] = $head;
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
                $lines[] = $error->getErrorType() . ": {$error->message} in {$error->file} on line {$error->line}";
            }

            $message = implode("\n", $lines);
        } else {
            $message = 'Something went wrong.';
        }

        return $message;
    }

    public static function verboseType($value)
    {
        switch ($type = gettype($value)) {
            case 'array':
                if (!empty($arg)) {
                    $found = [];
                    foreach ($arg as $item) {
                        $type = gettype($item);
                        if ($type == 'object') {
                            $type = get_class($item);
                        }
                        $found[$type] = true;
                    }

                    return count($found) === 1 ? $type . '[]' : 'Array';
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
            case 'boolean':
            case 'integer':
            case 'double':
            default:
                return var_export($value, true);
        }
    }
}

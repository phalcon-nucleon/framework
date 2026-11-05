<?php

declare(strict_types=1);

namespace Neutrino\Error;

use Phalcon\Logger\Enum;
use Throwable;
use UnitEnum;

/**
 * Text formatting of the errors, and mapping of the PHP error types.
 */
final class Helper
{
    /**
     * `E_STRICT`: the constant is deprecated since PHP 8.4, PHP no longer raises this type.
     */
    private const int E_STRICT = 2048;

    private function __construct() {}

    /**
     * The error as text: type, class and code of the exception, message, place, trace, and the previous exceptions.
     */
    public static function format(Error $error): string
    {
        return implode("\n", self::formatLines($error));
    }

    /**
     * The trace of an exception, a line per call.
     *
     * @return list<array{id: int, func: string, where: string, file?: string, line?: int}>
     */
    public static function formatExceptionTrace(Throwable $exception): array
    {
        $traces = [];

        foreach ($exception->getTrace() as $id => $trace) {
            $func = (isset($trace['class']) ? $trace['class'] . '->' : '') . $trace['function'];
            $item = ['id' => $id, 'func' => $func . '(' . implode(', ', self::verboseArgs($trace['args'] ?? [])) . ')'];

            if (isset($trace['file'])) {
                $item['file'] = self::path($trace['file']);
                $item['where'] = $item['file'];

                if (isset($trace['line'])) {
                    $item['line'] = $trace['line'];
                    $item['where'] .= '(' . $trace['line'] . ')';
                }
            } else {
                $item['where'] = '[internal function]';
            }

            $traces[] = $item;
        }

        return $traces;
    }

    /**
     * @param array<mixed> $args
     *
     * @return array<string>
     */
    public static function verboseArgs(array $args): array
    {
        return array_map(static fn(mixed $arg): string => self::verboseType($arg), $args);
    }

    /**
     * A short description of a value, for a trace: scalars are shown, long strings truncated, arrays summed up.
     */
    public static function verboseType(mixed $value, int $lvl = 0): string
    {
        return match (true) {
            is_array($value)    => self::verboseArray($value, $lvl),
            $value instanceof UnitEnum => self::shortClass($value::class) . '::' . $value->name,
            is_object($value)   => 'object(' . self::shortClass(get_debug_type($value)) . ')',
            $value === null     => 'null',
            is_string($value)   => self::verboseString($value),
            is_resource($value) => 'resource',
            is_scalar($value)   => var_export($value, true),
            default             => get_debug_type($value), // closed resource
        };
    }

    /**
     * The name of an error type, as "E_WARNING".
     */
    public static function getErrorType(int|string|null $code): string
    {
        return match ($code) {
            Error::EXCEPTION    => 'Uncaught exception',
            E_ERROR             => 'E_ERROR',
            E_WARNING           => 'E_WARNING',
            E_PARSE             => 'E_PARSE',
            E_NOTICE            => 'E_NOTICE',
            E_CORE_ERROR        => 'E_CORE_ERROR',
            E_CORE_WARNING      => 'E_CORE_WARNING',
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
            E_USER_ERROR        => 'E_USER_ERROR',
            E_USER_WARNING      => 'E_USER_WARNING',
            E_USER_NOTICE       => 'E_USER_NOTICE',
            self::E_STRICT      => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED        => 'E_DEPRECATED',
            E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
            default             => '(unknown error bit ' . $code . ')',
        };
    }

    /**
     * The severity and the name of an error type, as "Warning [E_WARNING]".
     */
    public static function verboseErrorType(int|string|null $code): string
    {
        $severity = match ($code) {
            Error::EXCEPTION => null,
            E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE, E_RECOVERABLE_ERROR, E_USER_ERROR => 'Fatal error',
            E_WARNING, E_USER_WARNING, E_CORE_WARNING, E_COMPILE_WARNING => 'Warning',
            E_NOTICE, E_USER_NOTICE => 'Notice',
            self::E_STRICT, E_DEPRECATED, E_USER_DEPRECATED => 'Deprecated',
            default => false,
        };

        return match ($severity) {
            null    => 'Uncaught exception',
            false   => '(unknown error bit ' . $code . ')',
            default => $severity . ' [' . self::getErrorType($code) . ']',
        };
    }

    /**
     * The logger level (`Phalcon\Logger\Enum`) of an error type.
     */
    public static function getLogType(int|string|null $code): int
    {
        return match ($code) {
            E_PARSE => Enum::CRITICAL,
            E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR => Enum::EMERGENCY,
            E_WARNING, E_USER_WARNING, E_CORE_WARNING, E_COMPILE_WARNING => Enum::WARNING,
            E_NOTICE, E_USER_NOTICE => Enum::NOTICE,
            self::E_STRICT, E_DEPRECATED, E_USER_DEPRECATED => Enum::INFO,
            default => Enum::ERROR, // exceptions, E_RECOVERABLE_ERROR, E_USER_ERROR
        };
    }

    /**
     * @return list<string>
     */
    private static function formatLines(Error $error, int $pass = 0): array
    {
        $lines = [self::getErrorType($error->type)];

        if ($error->exception !== null) {
            $lines[] = '  Class : ' . $error->exception::class;
            $lines[] = '  Code : ' . $error->code;
        }

        $lines[] = '  Message : ' . $error->message;
        $lines[] = ' in : ' . self::path($error->file) . '(' . $error->line . ')';

        if ($error->exception === null) {
            return $lines;
        }

        $lines[] = '';

        foreach (self::formatExceptionTrace($error->exception) as $trace) {
            $lines[] = '#' . $trace['id'] . ' ' . $trace['func'];
            $lines[] = str_repeat(' ', strlen((string) $trace['id']) + 2) . 'in : ' . $trace['where'];
        }

        $previous = $error->exception->getPrevious();

        if ($previous !== null) {
            $pass++;
            array_push($lines, '', '# Previous exception : ' . $pass, '', ...self::formatLines(Error::fromException($previous), $pass));
        }

        return $lines;
    }

    /**
     * @param array<mixed> $value
     */
    private static function verboseArray(array $value, int $lvl): string
    {
        if ($value === [] || $lvl > 0) {
            return 'array';
        }

        $types = [];
        foreach ($value as $item) {
            $types[get_debug_type($item)] = true;
        }

        $count = count($value);
        $scalars = count($types) === 1 && is_scalar($item ?? null);

        if ((count($types) === 1 && $count < ($scalars ? 6 : 3)) || (count($types) > 1 && $count < 5)) {
            $items = [];
            foreach ($value as $key => $item) {
                $items[] = (array_is_list($value) ? '' : var_export($key, true) . ' => ') . self::verboseType($item, $lvl + 1);
            }

            return 'array(' . implode(', ', $items) . ')';
        }

        return count($types) === 1 ? 'array.<' . array_key_first($types) . '>[' . $count . ']' : 'array[' . $count . ']';
    }

    private static function verboseString(string $value): string
    {
        if (defined('BASE_PATH') && str_starts_with($value, BASE_PATH . '/')) {
            $value = substr($value, strlen(BASE_PATH) + 1);
        }

        if (strlen($value) > 20) {
            return "'" . substr($value, 0, 8) . '...' . substr($value, -8) . "'[" . strlen($value) . ']';
        }

        return "'" . $value . "'";
    }

    private static function shortClass(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }

    private static function path(string $file): string
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', $file);
    }
}

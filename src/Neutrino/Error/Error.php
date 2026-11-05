<?php

declare(strict_types=1);

namespace Neutrino\Error;

use JsonSerializable;
use Throwable;

/**
 * A PHP error or an uncaught exception, as passed to the error writers.
 */
final readonly class Error implements JsonSerializable
{
    /**
     * The type of an uncaught exception.
     */
    public const int EXCEPTION = -1;

    /**
     * The error is an uncaught exception.
     */
    public bool $isException;

    /**
     * The error is a PHP error.
     */
    public bool $isError;

    /**
     * Readable type, as "Warning [E_WARNING]".
     */
    public string $typeStr;

    /**
     * Logger level (`Phalcon\Logger\Enum`).
     */
    public int $logLvl;

    /**
     * @param int        $type      `E_*` constant, or {@see self::EXCEPTION}
     * @param int|string $code      Code of the exception (a string for a `PDOException`), the type for an error
     */
    public function __construct(
        public int $type,
        public string $message,
        public string $file = '',
        public int $line = 0,
        public int|string $code = 0,
        public ?Throwable $exception = null,
    ) {
        $this->isException = $exception !== null;
        $this->isError = $exception === null;
        $this->typeStr = Helper::verboseErrorType($type);
        $this->logLvl = Helper::getLogType($type);
    }

    public static function fromException(Throwable $exception): self
    {
        return new self(
            self::EXCEPTION,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getCode(),
            $exception,
        );
    }

    public static function fromError(int $type, string $message, string $file = '', int $line = 0): self
    {
        return new self($type, $message, $file, $line, $type);
    }

    /**
     * An uncaught exception, or an error that stops the script.
     */
    public function isFatal(): bool
    {
        return $this->type === self::EXCEPTION || ($this->type & Handler::FATAL) !== 0;
    }

    /**
     * @return array{type: int, code: int|string, message: string, file: string, line: int, isException: bool, isError: bool, typeStr: string, logLvl: int, exception: array{class: class-string, code: int|string, message: string, traces: list<array{id: int, func: string, where: string, file?: string, line?: int}>}|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'type'        => $this->type,
            'code'        => $this->code,
            'message'     => $this->message,
            'file'        => $this->file,
            'line'        => $this->line,
            'isException' => $this->isException,
            'isError'     => $this->isError,
            'typeStr'     => $this->typeStr,
            'logLvl'      => $this->logLvl,
            'exception'   => $this->exception === null ? null : [
                'class'   => $this->exception::class,
                'code'    => $this->exception->getCode(),
                'message' => $this->exception->getMessage(),
                'traces'  => Helper::formatExceptionTrace($this->exception),
            ],
        ];
    }
}

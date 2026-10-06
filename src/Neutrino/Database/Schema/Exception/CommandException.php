<?php

declare(strict_types=1);

namespace Neutrino\Database\Schema\Exception;

use Neutrino\Support\Fluent;
use RuntimeException;
use Throwable;

/**
 * A {@see \Neutrino\Database\Schema\Blueprint} command failed.
 */
class CommandException extends RuntimeException
{
    public function __construct(public readonly Fluent $command, ?Throwable $previous = null, string $table = '')
    {
        $name = $command->get('name');

        parent::__construct(
            'Schema command "' . (is_string($name) ? $name : '?') . '"' . ($table === '' ? '' : " on \"$table\"")
            . ($previous === null ? ' failed.' : ': ' . $previous->getMessage()),
            0,
            $previous,
        );
    }

    public function __toString(): string
    {
        $str = static::class . PHP_EOL . 'Command Properties : ' . PHP_EOL;

        foreach ($this->command->getAttributes() as $key => $value) {
            $str .= "  - $key : " . (is_scalar($value) || $value === null ? var_export($value, true) : get_debug_type($value)) . PHP_EOL;
        }

        return $str . PHP_EOL . parent::__toString();
    }
}

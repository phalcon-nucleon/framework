<?php

namespace Neutrino\Database\Schema\Exception;

use Exception;
use Neutrino\Debug\Exceptions\Helper;
use Neutrino\Support\Fluent;
use Throwable;

/**
 * Class CommandException
 *
 * @package Neutrino\Database\Schema
 */
class CommandException extends Exception
{
    public $command;

    /**
     * CommandException constructor.
     *
     * @param \Neutrino\Support\Fluent $command
     * @param \Throwable|null          $previous
     */
    public function __construct(Fluent $command, Throwable $previous = null)
    {
        $this->command = $command;

        parent::__construct(null, 0, $previous);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $str = static::class . PHP_EOL;

        $str .= "Command Properties : " . PHP_EOL;

        foreach ($this->command as $key => $value) {
            $str .= "  - $key : " . Helper::verboseVar($value) . PHP_EOL;
        }

        return $str . PHP_EOL . parent::__toString();
    }
}

<?php

namespace Luxury\Cli\Output;

/**
 * Class ConsoleOutput
 *
 * @package Luxury\Cli\Output
 */
class ConsoleOutput
{
    /**
     * @var bool
     */
    protected $quiet;

    /**
     * ConsoleOutput constructor.
     *
     * @param bool $quiet
     */
    public function __construct($quiet = false)
    {
        $this->quiet = $quiet;

        if ($this->quiet) {
            ob_start();
        }
    }

    public function info($str)
    {
        $this->write(Decorate::info($str), true);
    }

    public function notice($str)
    {
        $this->write(Decorate::notice($str), true);
    }

    public function warn($str)
    {
        $this->write(Decorate::warn($str), true);
    }

    public function error($str)
    {
        $this->write(Decorate::error($str), true);
    }

    public function question($str)
    {
        $this->write(Decorate::question($str), true);
    }

    /**
     * {@inheritdoc}
     */
    public function write($message, $newline)
    {
        if ($this->quiet) {
            return;
        }

        if (false === @fwrite(STDOUT, $message) || ($newline && (false === @fwrite(STDOUT, PHP_EOL)))) {
            // should never happen
            throw new \RuntimeException('Unable to write output.');
        }

        fflush(STDOUT);
    }

    public function clean()
    {
        if ($this->quiet) {
            ob_end_clean();
        }
    }
}

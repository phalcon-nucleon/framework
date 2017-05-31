<?php

namespace Neutrino\Cli\Output;

/**
 * Class ConsoleOutput
 *
 *  @package Neutrino\Cli\Output
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

    /**
     * Write a string as information output.
     *
     * @param string $str
     */
    public function info($str)
    {
        $this->write(Decorate::info($str), true);
    }

    /**
     * Write a string as notice output.
     *
     * @param string $str
     */
    public function notice($str)
    {
        $this->write(Decorate::notice($str), true);
    }

    /**
     * Write a string as warning output.
     *
     * @param string $str
     */
    public function warn($str)
    {
        $this->write(Decorate::warn($str), true);
    }

    /**
     * Write a string as error output.
     *
     * @param string $str
     */
    public function error($str)
    {
        $this->write(Decorate::error($str), true);
    }

    /**
     * Write a string as question output.
     *
     * @param string $str
     */
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

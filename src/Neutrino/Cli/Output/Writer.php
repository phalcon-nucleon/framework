<?php

declare(strict_types=1);

namespace Neutrino\Cli\Output;

use RuntimeException;

/**
 * Console output. In quiet mode, nothing is written and the output buffer swallows any echo.
 */
class Writer
{
    private bool $buffering = false;

    public function __construct(protected bool $quiet = false)
    {
        if ($this->quiet) {
            $this->buffering = ob_start();
        }
    }

    public function line(string $str): void
    {
        $this->write($str, true);
    }

    public function info(string $str): void
    {
        $this->write(Decorate::info($str), true);
    }

    public function notice(string $str): void
    {
        $this->write(Decorate::notice($str), true);
    }

    public function warn(string $str): void
    {
        $this->write(Decorate::warn($str), true);
    }

    public function error(string $str): void
    {
        $this->write(Decorate::error($str), true);
    }

    public function question(string $str): void
    {
        $this->write(Decorate::question($str), true);
    }

    public function write(string $message, bool $newline): void
    {
        if ($this->quiet) {
            return;
        }

        if (@fwrite(STDOUT, $message . ($newline ? PHP_EOL : '')) === false) {
            throw new RuntimeException('Unable to write output.');
        }

        fflush(STDOUT);
    }

    /**
     * Ends the quiet mode buffer (once).
     */
    public function clean(): void
    {
        if ($this->buffering) {
            $this->buffering = false;
            ob_end_clean();
        }
    }
}

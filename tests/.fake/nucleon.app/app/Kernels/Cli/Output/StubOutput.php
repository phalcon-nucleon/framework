<?php

declare(strict_types=1);

namespace Fake\Kernels\Cli\Output;

use Neutrino\Cli\Output\Writer;

/**
 * Output kept in memory.
 */
class StubOutput extends Writer
{
    public string $out = '';

    public function write(string $message, bool $newline): void
    {
        if (!$this->quiet) {
            $this->out .= $message . ($newline ? PHP_EOL : '');
        }
    }
}

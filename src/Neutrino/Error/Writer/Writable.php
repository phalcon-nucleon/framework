<?php

declare(strict_types=1);

namespace Neutrino\Error\Writer;

use Neutrino\Error\Error;

/**
 * An output of the error handler, listed in the kernel's `$errorHandlerLvl`.
 */
interface Writable
{
    /**
     * Formats and writes an error.
     */
    public function handle(Error $error): void;
}

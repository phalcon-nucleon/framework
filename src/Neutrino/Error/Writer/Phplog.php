<?php

declare(strict_types=1);

namespace Neutrino\Error\Writer;

use Neutrino\Error\Error;
use Neutrino\Error\Helper;

/**
 * Writes the errors to the PHP log (`error_log`).
 */
final class Phplog implements Writable
{
    public function handle(Error $error): void
    {
        error_log(Helper::format($error));
    }
}

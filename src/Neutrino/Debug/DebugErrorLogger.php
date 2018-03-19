<?php

namespace Neutrino\Debug;

use Neutrino\Error\Error;
use Neutrino\Error\Writer\Writable;

/**
 * Class DebugErrorWriter
 *
 * @package App\Debug
 */
class DebugErrorLogger implements Writable
{
    private static $errors = [];

    /**
     * Format and write an error.
     *
     * @param \Neutrino\Error\Error $error
     *
     * @return void
     */
    public function handle(Error $error)
    {
        self::$errors[] = $error;
    }

    public static function errors()
    {
        return self::$errors;
    }
}

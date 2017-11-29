<?php

namespace Neutrino\Error\Writer;

use Neutrino\Error\Error;

/**
 * Interface Writable
 *
 * @package Neutrino\Error\Writer
 */
interface Writable
{
    /**
     * Format and write an error.
     *
     * @param \Neutrino\Error\Error $error
     *
     * @return void
     */
    public function handle(Error $error);
}

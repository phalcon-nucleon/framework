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
    public function handle(Error $error);
}

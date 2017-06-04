<?php

namespace Neutrino\Error\Writer;

use Neutrino\Error\Error;
use Neutrino\Error\Helper;

/**
 * Class Phplog
 *
 * @package Neutrino\Error\Writer
 */
class Phplog implements Writable
{

    public function handle(Error $error)
    {
        error_log(Helper::format($error), 0);
    }
}

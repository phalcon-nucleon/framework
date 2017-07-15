<?php

namespace Neutrino\Error\Writer;

use Neutrino\Cli\Output\Block;
use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Phalcon\Di;

/**
 * Class Cli
 *
 * @package     Neutrino\Error\Writer
 */
class Cli implements Writable
{
    public function handle(Error $error)
    {
        if (!$error->isFateful()) {
            return;
        }

        $di = Di::getDefault();

        if ($di && $di->has(Services\Cli::OUTPUT)) {
            $output = $di->getShared(Services\Cli::OUTPUT);

            $block = new Block($output, 'warn', ['padding' => 4]);

            $block->draw(explode("\n", Helper::format($error)));
        } else {
            echo Helper::format($error);
        }
    }
}

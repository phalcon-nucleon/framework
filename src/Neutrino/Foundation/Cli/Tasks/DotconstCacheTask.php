<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Task;
use Neutrino\Dotconst;

/**
 * Class ConfigCacheTask
 *
 * @package     Neutrino\Foundation\Cli\Tasks
 */
class DotconstCacheTask extends Task
{

    /**
     * Dotconst compile & cache.
     *
     * @description Dotconst compile & cache.
     *
     * @throws \Exception
     */
    public function mainAction()
    {
        $this->info('Generating dotconst compile & cache');

        self::generateCache();
    }

    public static function generateCache()
    {
        Dotconst\Compile::compile(BASE_PATH, BASE_PATH . '/bootstrap/compile');
    }
}

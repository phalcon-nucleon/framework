<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Output\Decorate;
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
        $this->output->write(Decorate::notice(str_pad('Generating dotconst cache', 40, ' ')), false);

        try {
            self::generateCache();

            $this->info("Success");
        } catch (\Exception $e) {
            $this->error("Error");
            $this->block([$e->getMessage()], 'error');
        }
    }

    public static function generateCache()
    {
        Dotconst\Compile::compile(BASE_PATH, BASE_PATH . '/bootstrap/compile');
    }
}

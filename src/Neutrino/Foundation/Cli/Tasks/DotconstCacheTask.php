<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Task;
use Neutrino\Dotconst;
use Throwable;

final class DotconstCacheTask extends Task
{
    #[Description('Cache the constants of the .const.ini files.')]
    public function mainAction(): void
    {
        $this->writer()->write(Decorate::notice(str_pad('Generating dotconst cache', 40)), false);

        try {
            self::generateCache();

            $this->info('Success');
        } catch (Throwable $e) {
            $this->error('Error');
            $this->block([$e->getMessage()], 'error');
        }
    }

    public static function generateCache(): void
    {
        Dotconst\Compile::compile(BASE_PATH, BASE_PATH . '/bootstrap/compile');
    }
}

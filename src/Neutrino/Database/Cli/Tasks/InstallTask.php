<?php

declare(strict_types=1);

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;

final class InstallTask extends BaseTask
{
    #[Description('Create the migration table.')]
    public function mainAction(): void
    {
        if ($this->storage()->storageExist()) {
            $this->notice('Migration table already exists.');

            return;
        }

        $this->storage()->createStorage();

        $this->info('Migration table created successfully.');
    }
}

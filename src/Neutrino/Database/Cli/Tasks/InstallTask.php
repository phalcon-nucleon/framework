<?php

namespace Neutrino\Database\Cli\Tasks;

/**
 * Class InstallTask
 *
 * @package Neutrino\Database\Cli\Tasks
 */
class InstallTask extends BaseTask
{
    /**
     * @description Create the migration storage.
     */
    public function mainAction()
    {
        $this->storage->createStorage();

        $this->info('Migration table created successfully.');
    }
}

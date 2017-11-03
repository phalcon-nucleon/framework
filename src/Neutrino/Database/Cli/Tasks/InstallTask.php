<?php

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Cli\Task;
use Neutrino\Database\Migrations\Storage\StorageInterface;

/**
 * Class InstallTask
 *
 * @package Neutrino\Database\Cli\Tasks
 */
class InstallTask extends Task
{
    use MigrationTrait;

    /**
     * @var \Neutrino\Database\Migrations\Storage\StorageInterface
     */
    protected $storage;

    /**
     * @description Create the migration storage.
     */
    public function mainAction()
    {
        $this->storage = $this->getDI()->get(StorageInterface::class);

        $this->storage->createStorage();

        $this->info('Migration table created successfully.');
    }
}

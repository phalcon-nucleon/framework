<?php

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Cli\Task;
use Neutrino\Database\Migrations\Migrator;

/**
 * Class MigrateTask
 *
 * @package Neutrino\Database\Cli\Tasks
 */
class MigrateTask extends Task
{
    use MigrationTrait;

    /**
     * The migrator instance.
     *
     * @var \Neutrino\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * @description Run the database migrations.
     *
     * @option      --database= : The database connection to use.
     * @option      --force : Force the operation to run when in production.
     * @option      --path= : The path of migrations files to be executed.
     * @option      --step : Force the migrations to be run so they can be rolled back individually.
     */
    public function mainAction()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $this->migrator = $this->getDI()->get(Migrator::class);

        if (!$this->assertStorageExist()) {
            return;
        }

        // Next, we will check to see if a path option has been defined. If it has
        // we will use the path relative to the root of this installation folder
        // so that migrations may be run for any path within the applications.
        $this->migrator->run($this->getMigrationPaths(), [
            'step' => $this->getOption('step'),
        ]);

        // Once the migrator has run we will grab the note output and send it out to
        // the console screen, since the migrator itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        foreach ($this->migrator->getNotes() as $note) {
            $this->line($note);
        }
    }

    /**
     * @return bool
     */
    protected function assertStorageExist()
    {
        if (!$this->migrator->storageExist()) {
            $this->warn('Migration Storage wasn\'t set. You must call migrate:install');

            return false;
        }

        return true;
    }
}

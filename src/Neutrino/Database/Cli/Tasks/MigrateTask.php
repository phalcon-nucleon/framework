<?php

namespace Neutrino\Database\Cli\Tasks;

/**
 * Class MigrateTask
 *
 * @package Neutrino\Database\Cli\Tasks
 */
class MigrateTask extends BaseTask
{
    /**
     * @description Run the database migrations.
     *
     * option      --database= : The database connection to use.
     * @option      --force : Force the operation to run when in production.
     * @option      --path= : The path of migrations files to be executed.
     * @option      --step : Force the migrations to be run so they can be rolled back individually.
     * @option      --pretend : Dump the SQL queries that would be run.
     */
    public function mainAction()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $this->prepareDatabase();

        // Next, we will check to see if a path option has been defined. If it has
        // we will use the path relative to the root of this installation folder
        // so that migrations may be run for any path within the applications.
        $this->migrator->run($this->getMigrationPaths(), [
            'step'    => (int)$this->getOption('step'),
            'pretend' => $this->getOption('pretend') ?: false
        ]);

        // Once the migrator has run we will grab the note output and send it out to
        // the console screen, since the migrator itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        foreach ($this->migrator->getNotes() as $note) {
            $this->line($note);
        }
    }

    /**
     * Prepare the migration database for running.
     *
     * @return void
     */
    protected function prepareDatabase()
    {
        if (!$this->migrator->storageExist()) {
            $this->callTask(InstallTask::class, 'main', $this->arguments, $this->options);
        }
    }
}

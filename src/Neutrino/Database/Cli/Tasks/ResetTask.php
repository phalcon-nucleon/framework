<?php

namespace Neutrino\Database\Cli\Tasks;

/**
 * Class ResetTask
 *
 * @package Neutrino\Database\Cli\Tasks
 */
class ResetTask extends BaseTask
{
    /**
     * @description Rollback all database migrations.
     *
     * @option      -f, --force : Force the operation to run when in production.
     * @option      --path : The path of migrations files to be executed.
     * @option      --step : The number of migrations to be reverted & re-run.
     */
    public function mainAction()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        // First, we'll make sure that the migration table actually exists before we
        // start trying to rollback and re-run all of the migrations. If it's not
        // present we'll just bail out with an info message for the developers.
        if (!$this->migrator->storageExist()) {
            $this->notice('Migration table not found.');

            return;
        }

        $this->migrator->reset($this->getMigrationPaths(), [
            'pretend' => $this->getOption('pretend') ?: false
        ]);

        // Once the migrator has run we will grab the note output and send it out to
        // the console screen, since the migrator itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        foreach ($this->migrator->getNotes() as $note) {
            $this->line($note);
        }
    }

}

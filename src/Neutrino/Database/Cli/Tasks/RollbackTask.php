<?php

namespace Neutrino\Database\Cli\Tasks;

/**
 * Class RollbackTask
 *
 * @package Neutrino\Database\Cli\Tasks
 */
class RollbackTask extends BaseTask
{
    /**
     * @description Rollback the last database migration.
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

        $this->migrator->rollback(
            $this->getMigrationPaths(),
            ['step' => (int)$this->getOption('step')]
        );

        // Once the migrator has run we will grab the note output and send it out to
        // the console screen, since the migrator itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        foreach ($this->migrator->getNotes() as $note) {
            $this->output->line($note);
        }
    }
}

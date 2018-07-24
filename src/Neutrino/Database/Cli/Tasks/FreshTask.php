<?php

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Database\Schema\Builder;

/**
 * Class FreshTask
 *
 * @package Neutrino\Database\Cli\Tasks
 */
class FreshTask extends BaseTask
{
    /**
     * @description Drop all tables and re-run all migrations.
     *
     * @option -f, --force Force the operation to run when in production.
     * @option --path The path of migrations files to be executed.
     * @option --pretend : Dump the SQL queries that would be run.
     */
    public function mainAction()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $this->dropAllTables();

        $this->info('Dropped all tables successfully.');

        $this->callTask(MigrateTask::class, 'main', $this->arguments, $this->options);
    }

    /**
     * Drop all of the database tables.
     *
     * @return void
     */
    protected function dropAllTables()
    {
        (new Builder())->dropAllTables();
    }
}

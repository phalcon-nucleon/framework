<?php

namespace Neutrino\Database\Cli\Tasks;

/**
 * Class ResfreshTask
 *
 * @package Neutrino\Database\Cli\Tasks
 */
class RefreshTask extends BaseTask
{
    /**
     * @description Reset and re-run all migrations.
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

        $step = $this->getOption('step') ?: 0;

        if ($step > 0) {
            $this->runRollback();
        } else {
            $this->runReset();
        }

        $this->runMigrate();
    }

    /**
     * Run the rollback command.
     *
     * @return void
     */
    protected function runRollback()
    {
        $this->callTask(RollbackTask::class, 'main', $this->arguments, $this->options);
    }

    /**
     * Run the reset command.
     *
     * @return void
     */
    protected function runReset()
    {
        $this->callTask(ResetTask::class, 'main', $this->arguments, $this->options);
    }

    /**
     * Run the migrate command.
     *
     * @return void
     */
    protected function runMigrate()
    {
        $this->callTask(MigrateTask::class, 'main', $this->arguments, $this->options);
    }
}

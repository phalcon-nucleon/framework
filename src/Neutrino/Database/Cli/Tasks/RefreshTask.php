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

        $path = $this->getOption('path');
        $force = $this->getOption('force');
        $step = $this->getOption('step') ?: 0;

        if ($step > 0) {
            $this->runRollback($path, $step, $force);
        } else {
            $this->runReset($path, $force);
        }

    }

    /**
     * Run the rollback command.
     *
     * @param  string $path
     * @param  bool   $step
     * @param  bool   $force
     *
     * @return void
     */
    protected function runRollback($path, $step, $force)
    {
        $this->dispatcher->forward([
            'controller' => ''
        ]);
    }

    /**
     * Run the reset command.
     *
     * @param  string $path
     * @param  bool   $force
     *
     * @return void
     */
    protected function runReset($path, $force)
    {
        $this->dispatcher->forward([
            'controller' => ''
        ]);
    }

}

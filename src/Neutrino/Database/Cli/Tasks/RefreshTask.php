<?php

declare(strict_types=1);

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Attribute\Option;

final class RefreshTask extends BaseTask
{
    #[Description('Reset (or roll back) and re-run the migrations.')]
    #[Option('--database={name}', 'Connection of the migrations that do not declare theirs.')]
    #[Option('-f, --force', 'Force the operation to run in production.')]
    #[Option('--path={path}', 'Path of the migrations, relative to the application.')]
    #[Option('--step={n}', 'Number of migrations to roll back and re-run (all otherwise).')]
    public function mainAction(): void
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $this->prepareStorage();

        $paths = $this->getMigrationPaths();
        $migrator = $this->migrator();
        $steps = RollbackTask::steps($this->getOption('step'));

        $steps > 0 ? $migrator->rollback($paths, ['step' => $steps]) : $migrator->reset($paths);
        $this->writeNotes();

        $migrator->run($paths);
        $this->writeNotes();
    }
}

<?php

declare(strict_types=1);

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Attribute\Option;

final class RollbackTask extends BaseTask
{
    #[Description('Rollback the last batch of migrations.')]
    #[Option('--database={name}', 'Connection of the migrations that do not declare theirs.')]
    #[Option('-f, --force', 'Force the operation to run in production.')]
    #[Option('--path={path}', 'Path of the migrations, relative to the application.')]
    #[Option('--step={n}', 'Number of migrations to roll back (the last batch otherwise).')]
    #[Option('--pretend', 'Dump the SQL queries that would be run.')]
    public function mainAction(): void
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $this->migrator()->rollback($this->getMigrationPaths(), [
            'step'    => self::steps($this->getOption('step')),
            'pretend' => $this->pretending(),
        ]);

        $this->writeNotes();
    }

    public static function steps(mixed $step): int
    {
        return is_numeric($step) ? max(0, (int) $step) : 0;
    }
}

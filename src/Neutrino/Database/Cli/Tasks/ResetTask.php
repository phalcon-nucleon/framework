<?php

declare(strict_types=1);

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Attribute\Option;

final class ResetTask extends BaseTask
{
    #[Description('Rollback all database migrations.')]
    #[Option('--database={name}', 'Connection of the migrations that do not declare theirs.')]
    #[Option('-f, --force', 'Force the operation to run in production.')]
    #[Option('--path={path}', 'Path of the migrations, relative to the application.')]
    #[Option('--pretend', 'Dump the SQL queries that would be run.')]
    public function mainAction(): void
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        if (!$this->storage()->storageExist()) {
            $this->notice('Migration table not found.');

            return;
        }

        $this->migrator()->reset($this->getMigrationPaths(), ['pretend' => $this->pretending()]);

        $this->writeNotes();
    }
}

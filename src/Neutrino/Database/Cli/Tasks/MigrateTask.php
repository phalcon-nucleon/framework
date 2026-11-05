<?php

declare(strict_types=1);

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Attribute\Option;

final class MigrateTask extends BaseTask
{
    #[Description('Run the database migrations.')]
    #[Option('--database={name}', 'Connection of the migrations that do not declare theirs.')]
    #[Option('-f, --force', 'Force the operation to run in production.')]
    #[Option('--path={path}', 'Path of the migrations, relative to the application.')]
    #[Option('--step', 'One batch per migration, so that they can be rolled back one by one.')]
    #[Option('--pretend', 'Dump the SQL queries that would be run.')]
    public function mainAction(): void
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $this->prepareStorage();

        $this->migrator()->run($this->getMigrationPaths(), [
            'step'    => $this->hasOption('step'),
            'pretend' => $this->pretending(),
        ]);

        $this->writeNotes();
    }
}

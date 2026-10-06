<?php

declare(strict_types=1);

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Attribute\Option;
use Neutrino\Cli\Output\Decorate;

final class StatusTask extends BaseTask
{
    #[Description('Show the status of each migration.')]
    #[Option('--path={path}', 'Path of the migrations, relative to the application.')]
    public function mainAction(): void
    {
        if (!$this->storage()->storageExist()) {
            $this->error('Migration table not found.');

            return;
        }

        $ran = array_flip($this->storage()->getRan());
        $rows = [];

        foreach (array_keys($this->migrator()->getMigrationFiles($this->getMigrationPaths())) as $name) {
            $rows[] = [
                'Ran?'      => isset($ran[$name]) ? Decorate::info('Y') : Decorate::apply('N', 'red'),
                'Migration' => $name,
            ];
        }

        if ($rows === []) {
            $this->error('No migrations found.');

            return;
        }

        $this->table($rows);
    }
}

<?php

declare(strict_types=1);

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Cli\Attribute\Argument;
use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Attribute\Option;

final class MakerTask extends BaseTask
{
    #[Description('Create a new migration file.')]
    #[Argument('name', 'The name of the migration (create_users_table).')]
    #[Option('--create={table}', 'The table to be created.')]
    #[Option('--table={table}', 'The table to be modified.')]
    #[Option('--path={path}', 'Where to create the file, relative to the application.')]
    public function mainAction(): void
    {
        $name = $this->getArg('name');
        $name = is_string($name) ? trim($name) : '';
        $table = $this->getOption('table');
        $table = is_string($table) && $table !== '' ? $table : null;
        $create = $this->getOption('create');

        // --create=<table> is a shortcut of --table=<table> --create.
        if ($table === null && is_string($create) && $create !== '') {
            $table = $create;
        }
        $create = $create !== null && $create !== false;

        // create_<table>_table
        if ($table === null && preg_match('/^create_(\w+)_table$/', $name, $matches) === 1) {
            $table = $matches[1];
            $create = true;
        }

        $file = $this->creator()->create($name, $this->getMigrationPath(), $table, $create);

        $this->info('Created Migration: ' . pathinfo($file, PATHINFO_FILENAME));
    }
}

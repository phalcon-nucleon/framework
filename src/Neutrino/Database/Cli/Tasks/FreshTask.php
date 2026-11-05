<?php

declare(strict_types=1);

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Attribute\Option;
use Neutrino\Database\Migrations\Migration;
use Neutrino\Database\Migrations\Storage\DatabaseStorage;
use Neutrino\Database\Schema\Builder;
use Neutrino\Support\Db;
use Phalcon\Db\Adapter\AdapterInterface;

final class FreshTask extends BaseTask
{
    #[Description('Drop all tables and re-run all migrations.')]
    #[Option('--database={name}', 'Connection of the migrations that do not declare theirs.')]
    #[Option('-f, --force', 'Force the operation to run in production.')]
    #[Option('--path={path}', 'Path of the migrations, relative to the application.')]
    public function mainAction(): void
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        foreach ($this->connections() as $connection) {
            (new Builder($connection))->dropAllTables();
        }

        $this->info('Dropped all tables successfully.');

        $this->prepareStorage();

        $this->migrator()->run($this->getMigrationPaths());
        $this->writeNotes();
    }

    /**
     * The connections to empty: that of the migrations (`--database` or the default one), those the migrations
     * declare, and that of the migration table.
     *
     * @return array<int, AdapterInterface>
     */
    private function connections(): array
    {
        $migrator = $this->migrator();
        $database = $this->getOption('database');
        $names = [is_string($database) && $database !== '' ? $database : null];

        foreach ($migrator->getMigrationFiles($this->getMigrationPaths()) as $file) {
            $migration = $migrator->resolve($file);

            if ($migration instanceof Migration && $migration->getConnection() !== null) {
                $names[] = $migration->getConnection();
            }
        }

        $connections = [];
        foreach (array_unique($names, SORT_REGULAR) as $name) {
            $connection = Db::connection($name);
            $connections[spl_object_id($connection)] = $connection;
        }

        $storage = $this->storage();
        if ($storage instanceof DatabaseStorage) {
            $connection = $storage->connection();
            $connections[spl_object_id($connection)] = $connection;
        }

        return $connections;
    }
}

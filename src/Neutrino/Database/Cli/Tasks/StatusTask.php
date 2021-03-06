<?php

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Cli\Output\Decorate;

/**
 * Class StatusTask
 *
 * @package Neutrino\Database\Cli\Tasks
 */
class StatusTask extends BaseTask
{
    /**
     * @description Show the status of each migration.
     */
    public function mainAction()
    {
        if (!$this->migrator->storageExist()) {
            $this->error('No migrations found.');

            return;
        }

        $ran = $this->migrator->getStorage()->getRan();

        if (count($migrations = $this->getStatusFor($ran)) > 0) {
            $this->table($migrations);
        } else {
            $this->error('No migrations found.');
        }
    }

    /**
     * Get the status for the given ran migrations.
     *
     * @param array $ran
     *
     * @return array
     */
    protected function getStatusFor(array $ran)
    {
        return array_map(function ($migration) use ($ran) {
            $migrationName = $this->migrator->getMigrationName($migration);

            return [
                'Ran?'      => in_array($migrationName, $ran)
                    ? Decorate::info('Y')
                    : Decorate::apply('N', 'red'),
                'Migration' => $migrationName
            ];
        }, $this->getAllMigrationFiles());
    }

    /**
     * Get an array of all of the migration files.
     *
     * @return array
     */
    protected function getAllMigrationFiles()
    {
        return $this->migrator->getMigrationFiles($this->getMigrationPaths());
    }
}

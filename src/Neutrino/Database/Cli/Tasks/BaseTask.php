<?php

declare(strict_types=1);

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Cli\Task;
use Neutrino\Constants\Env;
use Neutrino\Constants\Services;
use Neutrino\Database\Migrations\MigrationCreator;
use Neutrino\Database\Migrations\Migrator;
use Neutrino\Database\Migrations\Storage\StorageInterface;
use Phalcon\Config\Config;

/**
 * Base of the migration commands: services, paths (`--path`), connection (`--database`) and confirmation in
 * production (`--force`).
 */
abstract class BaseTask extends Task
{
    protected function migrator(): Migrator
    {
        /** @var Migrator $migrator */
        $migrator = $this->getDI()->getShared(Migrator::class);
        $database = $this->getOption('database');
        $migrator->setConnection(is_string($database) && $database !== '' ? $database : null);

        return $migrator;
    }

    protected function storage(): StorageInterface
    {
        /** @var StorageInterface */
        return $this->getDI()->getShared(StorageInterface::class);
    }

    protected function creator(): MigrationCreator
    {
        /** @var MigrationCreator */
        return $this->getDI()->getShared(MigrationCreator::class);
    }

    /**
     * In production, asks for a confirmation, unless `--force`.
     */
    protected function confirmToProceed(): bool
    {
        if (APP_ENV !== Env::PRODUCTION || $this->hasOption('f', 'force')) {
            return true;
        }

        $this->warn('You will run migration on production environment');

        return $this->confirm('Are you sure you want to run migration ?', false);
    }

    /**
     * Creates the migration storage if it does not exist.
     */
    protected function prepareStorage(): void
    {
        if (!$this->storage()->storageExist()) {
            $this->storage()->createStorage();

            $this->info('Migration table created successfully.');
        }
    }

    /**
     * Get migration path (either specified by '--path' option or default location).
     */
    protected function getMigrationPath(): string
    {
        $path = $this->getOption('path');

        if (is_string($path) && $path !== '') {
            return BASE_PATH . '/' . ltrim($path, '/');
        }

        $config = $this->getDI()->getShared(Services::CONFIG);
        $path = $config instanceof Config ? $config->path('migrations.path') : null;

        return is_string($path) && $path !== '' ? $path : BASE_PATH . '/migrations';
    }

    /**
     * Get all of the migration paths: `--path`, or the default one and those registered on the migrator.
     *
     * @return list<string>
     */
    protected function getMigrationPaths(): array
    {
        if (is_string($this->getOption('path')) && $this->getOption('path') !== '') {
            return [$this->getMigrationPath()];
        }

        return array_values(array_unique([$this->getMigrationPath(), ...$this->migrator()->paths()]));
    }

    protected function pretending(): bool
    {
        return $this->hasOption('pretend');
    }

    /**
     * Writes the notes of the migrator.
     */
    protected function writeNotes(): void
    {
        foreach ($this->migrator()->getNotes() as $note) {
            $this->line($note);
        }
    }
}

<?php

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Cli\Task;
use Neutrino\Constants\Env;
use Neutrino\Database\Migrations\MigrationCreator;
use Neutrino\Database\Migrations\Migrator;
use Neutrino\Database\Migrations\Storage\StorageInterface;

/**
 * Class BaseTask
 *
 * @package Neutrino\Database\Cli\Tasks
 */
abstract class BaseTask extends Task
{
    /**
     * The migrator instance.
     *
     * @var \Neutrino\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * The Storage instance
     *
     * @var \Neutrino\Database\Migrations\Storage\StorageInterface
     */
    protected $storage;

    /**
     * The migration creator instance.
     *
     * @var \Neutrino\Database\Migrations\MigrationCreator
     */
    protected $creator;

    protected function onConstruct()
    {
        parent::onConstruct();

        $this->migrator = $this->getDI()->get(Migrator::class);
        $this->storage = $this->getDI()->get(StorageInterface::class);
        $this->creator = $this->getDI()->get(MigrationCreator::class);
    }

    /**
     * @return bool
     */
    protected function confirmToProceed()
    {
        if (APP_ENV === Env::PRODUCTION) {
            $this->warn("You will run migration on production environnement");

            if ($this->hasOption('f', 'force')) {
                return true;
            }

            return $this->confirm("Are you sure you want to run migration ?", false);
        }

        return true;
    }

    /**
     * Get migration path (either specified by '--path' option or default location).
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        if (!is_null($targetPath = $this->getOption('path'))) {
            return BASE_PATH . '/' . $targetPath;
        }

        return $this->config->migrations->path;
    }

    /**
     * Get all of the migration paths.
     *
     * @return array
     */
    protected function getMigrationPaths()
    {
        // Here, we will check to see if a path option has been defined. If it has we will
        // use the path relative to the root of the installation folder so our database
        // migrations may be run for any customized path from within the application.
        if ($this->hasOption('path') && $this->getOption('path')) {
            return array_map(function ($path) {
                return BASE_PATH . '/' . $path;
            }, (array)$this->getOption('path'));
        }

        return array_merge(
            [$this->getMigrationPath()], $this->migrator->paths()
        );
    }
}

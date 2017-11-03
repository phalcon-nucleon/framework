<?php

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Constants\Env;

/**
 * Trait MigrationTrait
 *
 * @package Neutrino\Database\Cli\Tasks
 */
trait MigrationTrait
{
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

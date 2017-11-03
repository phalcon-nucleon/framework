<?php

namespace Neutrino\Database\Cli\Tasks;

use Neutrino\Cli\Task;
use Neutrino\Database\Migrations\MigrationCreator;

/**
 * Class MigrationMakeTask
 *
 * @package Neutrino\Database\Cli\Tasks
 */
class MakerTask extends Task
{
    use MigrationTrait;

    /**
     * The migration creator instance.
     *
     * @var \Neutrino\Database\Migrations\MigrationCreator
     */
    protected $creator;

    /**
     * @description Create a new migration file.
     *
     * @argument    name : The name of the migration.
     *
     * @option      --create= : The table to be created.
     * @option      --table= : The table to be migrate.
     * @option      --path= : The location where the migration file should be created.
     */
    public function mainAction()
    {
        $this->creator = $this->getDI()->get(MigrationCreator::class);

        // It's possible for the developer to specify the tables to modify in this
        // schema operation. The developer may also specify if this table needs
        // to be freshly created so we can create the appropriate migrations.
        $name = trim($this->getArg('name'));
        $table = $this->getOption('table');
        $create = $this->getOption('create', false);

        // If no table was given as an option but a create option is given then we
        // will use the "create" option as the table name. This allows the devs
        // to pass a table name into this option as a short-cut for creating.
        if (!$table && is_string($create)) {
            $table = $create;

            $create = true;
        }

        // Next, we will attempt to guess the table name if this the migration has
        // "create" in the name. This will allow us to provide a convenient way
        // of creating migrations that create new tables for the application.
        if (!$table) {
            if (preg_match('/^create_(\w+)_table$/', $name, $matches)) {
                $table = $matches[1];

                $create = true;
            }
        }

        // Now we are ready to write the migration out to disk. Once we've written
        // the migration out, we will dump-autoload for the entire framework to
        // make sure that the migrations are registered by the class loaders.
        $this->writeMigration($name, $table, $create);
    }

    /**
     * Write the migration file to disk.
     *
     * @param  string $name
     * @param  string $table
     * @param  bool   $create
     */
    protected function writeMigration($name, $table, $create)
    {
        $file = pathinfo($this->creator->create(
            $name, $this->getMigrationPath(), $table, $create
        ), PATHINFO_FILENAME);

        $this->info("Created Migration: {$file}");
    }
}

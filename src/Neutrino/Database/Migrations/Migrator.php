<?php

namespace Neutrino\Database\Migrations;

//use Highlight\Highlighter;
//use Highlight\Languages\SQL;
//use Highlight\Renders\Shell;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Database\Migrations\Prefix\PrefixInterface;
use Neutrino\Database\Migrations\Storage\StorageInterface;
use Neutrino\Database\Schema\Builder;
use Neutrino\Support\Arr;
use Neutrino\Support\Db;
use Neutrino\Support\Str;

/**
 * Class Migrator
 *
 * @package Neutrino\Database\Migrations
 */
class Migrator
{
    /**
     * The migration repository implementation.
     *
     * @var \Neutrino\Database\Migrations\Storage\StorageInterface
     */
    protected $storage;

    /**
     * The migration repository implementation.
     *
     * @var \Neutrino\Database\Migrations\Prefix\PrefixInterface
     */
    protected $prefix;

    /**
     * The notes for the current operation.
     *
     * @var array
     */
    protected $notes = [];

    /**
     * The paths to all of the migration files.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * Migrator constructor.
     *
     * @param \Neutrino\Database\Migrations\Storage\StorageInterface $repository
     * @param \Neutrino\Database\Migrations\Prefix\PrefixInterface   $prefix
     */
    public function __construct(StorageInterface $repository, PrefixInterface $prefix)
    {
        $this->storage = $repository;
        $this->prefix = $prefix;
    }

    /**
     * Run the pending migrations at a given path.
     *
     * @param array|string $paths
     * @param array        $options
     *
     * @return array
     * @throws \Exception
     */
    public function run($paths = [], array $options = [])
    {
        $this->notes = [];

        // Once we grab all of the migration files for the path, we will compare them
        // against the migrations that have already been run for this package then
        // run each of the outstanding migrations against a database connection.
        $files = $this->getMigrationFiles($paths);

        $this->requireFiles($migrations = $this->pendingMigrations(
            $files, $this->storage->getRan()
        ));

        // Once we have all these migrations that are outstanding we are ready to run
        // we will go ahead and run them "up". This will execute each migration as
        // an operation against a database. Then we'll return this list of them.
        $this->runPending($migrations, $options);

        return $migrations;
    }

    /**
     * Get the migration files that have not yet run.
     *
     * @param array $files
     * @param array $ran
     *
     * @return array
     */
    protected function pendingMigrations($files, $ran)
    {
        $pending = [];

        foreach ($files as $file) {
            if (!in_array($this->getMigrationName($file), $ran)) {
                $pending[] = $file;
            }
        }

        return $pending;
    }

    /**
     * Run an array of migrations.
     *
     * @param array $migrations
     * @param array $options
     *
     * @return void
     * @throws \Exception
     */
    public function runPending(array $migrations, array $options = [])
    {
        // First we will just make sure that there are any migrations to run. If there
        // aren't, we will just make a note of it to the developer so they're aware
        // that all of the migrations have been run against this database system.
        if (count($migrations) == 0) {
            $this->note(Decorate::info('Nothing to migrate.'));

            return;
        }

        // Next, we will get the next batch number for the migrations so we can insert
        // correct batch number in the database migrations repository when we store
        // each migration's execution. We will also extract a few of the options.
        $batch = $this->storage->getNextBatchNumber();

        $step = Arr::get($options, 'step', 0);
        $pretend = Arr::get($options, 'pretend', false);

        // Once we have the array of migrations, we will spin through them and run the
        // migrations "up" so the changes are made to the databases. We'll then log
        // that the migration was run so we don't repeat it next time we execute.
        foreach ($migrations as $file) {
            $this->runUp($file, $batch, $pretend);

            if ($step) {
                $batch++;
            }
        }
    }

    /**
     * Run "up" a migration instance.
     *
     * @param string $file
     * @param int    $batch
     * @param bool   $pretend
     *
     * @return void
     * @throws \Exception
     */
    protected function runUp($file, $batch, $pretend = false)
    {
        // First we will resolve a "real" instance of the migration class from this
        // migration file name. Once we have the instances we can run the actual
        // command such as "up" or "down", or we can just simulate the action.
        $migration = $this->resolve(
            $name = $this->getMigrationName($file)
        );

        if ($pretend) {
            $this->pretendToRun($name, $migration, 'up');

            return;
        }

        $this->note(Decorate::notice('Migrating:') . " {$name}");

        $this->runMigration($migration, 'up');

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.
        $this->storage->log($name, $batch);

        $this->note(Decorate::info('Migrated:') . " {$name}");
    }

    /**
     * Rollback the last migration operation.
     *
     * @param array|string $paths
     * @param array        $options
     *
     * @return array
     * @throws \Exception
     */
    public function rollback($paths = [], array $options = [])
    {
        $this->notes = [];

        // We want to pull in the last batch of migrations that ran on the previous
        // migration operation. We'll then reverse those migrations and run each
        // of them "down" to reverse the last migration "operation" which ran.
        $migrations = $this->getMigrationsForRollback($options);

        if (count($migrations) === 0) {
            $this->note(Decorate::info('Nothing to rollback.'));

            return [];
        } else {
            return $this->rollbackMigrations($migrations, $paths, $options);
        }
    }

    /**
     * Get the migrations for a rollback operation.
     *
     * @param  array $options
     *
     * @return array
     */
    protected function getMigrationsForRollback(array $options)
    {
        $steps = Arr::get($options, 'step', 0);

        if ($steps > 0) {
            return $this->storage->getMigrations($steps);
        } else {
            return $this->storage->getLast();
        }
    }

    /**
     * Rollback the given migrations.
     *
     * @param array        $migrations
     * @param array|string $paths
     * @param array         $options
     *
     * @return array
     * @throws \Exception
     */
    protected function rollbackMigrations(array $migrations, $paths, array $options)
    {
        $rolledBack = [];

        $pretend = Arr::get($options, 'pretend', false);

        $this->requireFiles($files = $this->getMigrationFiles($paths));

        // Next we will run through all of the migrations and call the "down" method
        // which will reverse each migration in order. This getLast method on the
        // repository already returns these migration's names in reverse order.
        foreach ($migrations as $migration) {
            $migration = (object)$migration;

            if (!$file = Arr::get($files, $migration->migration)) {
                continue;
            }

            $rolledBack[] = $file;

            $this->runDown($file, $migration, $pretend);
        }

        return $rolledBack;
    }

    /**
     * Rolls all of the currently applied migrations back.
     *
     * @param array|string $paths
     * @param array        $options
     *
     * @return array
     * @throws \Exception
     */
    public function reset($paths = [], array $options = [])
    {
        $this->notes = [];

        // Next, we will reverse the migration list so we can run them back in the
        // correct order for resetting this database. This will allow us to get
        // the database back into its "empty" state ready for the migrations.
        $migrations = array_reverse($this->storage->getRan());

        if (count($migrations) === 0) {
            $this->note(Decorate::info('Nothing to rollback.'));

            return [];
        } else {
            return $this->resetMigrations($migrations, $paths, $options);
        }
    }

    /**
     * Reset the given migrations.
     *
     * @param array $migrations
     * @param array $paths
     * @param array $options
     *
     * @return array
     * @throws \Exception
     */
    protected function resetMigrations(array $migrations, array $paths, array $options)
    {
        // Since the getRan method that retrieves the migration name just gives us the
        // migration name, we will format the names into objects with the name as a
        // property on the objects so that we can pass it to the rollback method.
        $migrations = array_map(function ($m) {
            return (object)['migration' => $m];
        }, $migrations);

        return $this->rollbackMigrations($migrations, $paths, $options);
    }

    /**
     * Run "down" a migration instance.
     *
     * @param string $file
     * @param object $migration
     * @param bool   $pretend
     *
     * @return void
     * @throws \Exception
     */
    protected function runDown($file, $migration, $pretend = false)
    {
        // First we will get the file name of the migration so we can resolve out an
        // instance of the migration. Once we get an instance we can either run a
        // pretend execution of the migration or we can run the real migration.
        $instance = $this->resolve(
            $name = $this->getMigrationName($file)
        );

        if ($pretend) {
            $this->pretendToRun($name, $instance, 'down');

            return;
        }

        $this->note(Decorate::notice('Rolling back:') . " {$name}");

        $this->runMigration($instance, 'down');

        // Once we have successfully run the migration "down" we will remove it from
        // the migration repository so it will be considered to have not been run
        // by the application then will be able to fire by any later operation.
        $this->storage->delete($migration->migration);

        $this->note(Decorate::info('Rolled back:') . " {$name}");
    }

    /**
     * Pretend to run the migrations.
     *
     * @param $name
     * @param $instance
     * @param $method
     */
    protected function pretendToRun($name, $instance, $method)
    {
        $this->note(Decorate::info($name) . ': ');

        $queries = Db::pretend(function () use ($instance, $method) {
            $this->runMigration($instance, $method);
        });

        //$sql = Highlighter::factory(SQL::class, Shell::class);

        foreach ($queries as $query) {
            $this->note($query /*$sql->highlight($query)*/);
        }
    }

    /**
     * Run a migration inside a transaction if the database supports it.
     *
     * @param \Neutrino\Database\Migrations\Migration $migration
     * @param string                                  $method
     *
     * @return void
     */
    protected function runMigration($migration, $method)
    {
        if (method_exists($migration, $method)) {
            $migration->{$method}(new Builder());
        }
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param  string $file
     *
     * @return \Neutrino\Database\Migrations\Migration
     */
    public function resolve($file)
    {
        $class = Str::studly($this->prefix->deletePrefix($file));

        return new $class;
    }

    /**
     * Get all of the migration files in a given path.
     *
     * @param  string|array $paths
     *
     * @return array
     */
    public function getMigrationFiles($paths)
    {
        $paths = array_map(function ($path) {
            return glob($path . '/*_*.php');
        }, (array)$paths);

        $paths = array_filter(Arr::collapse($paths));

        $migrations = [];
        foreach ($paths as $path) {
            $migrations[$this->getMigrationName($path)] = $path;
        }

        ksort($migrations, SORT_REGULAR);

        return $migrations;
    }

    /**
     * Require in all the migration files in a given path.
     *
     * @param  array $files
     *
     * @return void
     */
    public function requireFiles(array $files)
    {
        foreach ($files as $file) {
            require_once $file;
        }
    }

    /**
     * Get the name of the migration.
     *
     * @param  string $path
     *
     * @return string
     */
    public function getMigrationName($path)
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Register a custom migration path.
     *
     * @param  string $path
     *
     * @return void
     */
    public function path($path)
    {
        $this->paths = array_unique(array_merge($this->paths, [$path]));
    }

    /**
     * Get all of the custom migration paths.
     *
     * @return array
     */
    public function paths()
    {
        return $this->paths;
    }

    /**
     * Get the migration storage instance.
     *
     * @return \Neutrino\Database\Migrations\Storage\StorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function storageExist()
    {
        return $this->storage->storageExist();
    }

    /**
     * Raise a note event for the migrator.
     *
     * @param  string $message
     *
     * @return void
     */
    protected function note($message)
    {
        $this->notes[] = $message;
    }

    /**
     * Get the notes for the last operation.
     *
     * @return array
     */
    public function getNotes()
    {
        return $this->notes;
    }
}

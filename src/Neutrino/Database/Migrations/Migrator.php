<?php

declare(strict_types=1);

namespace Neutrino\Database\Migrations;

use Neutrino\Cli\Output\Decorate;
use Neutrino\Database\Migrations\Prefix\PrefixInterface;
use Neutrino\Database\Migrations\Storage\StorageInterface;
use Neutrino\Database\Schema\Builder;
use Neutrino\Support\Db;
use Neutrino\Support\Str;
use RuntimeException;
use Tempest\Highlight\Highlighter;
use Tempest\Highlight\Themes\LightTerminalTheme;
use Throwable;

/**
 * Runs the migrations of the migration paths, and rolls them back, by batch.
 *
 * Options of `run()`, `rollback()` and `reset()`:
 * - `step`: `run()`: one batch per migration; `rollback()`: number of migrations to roll back (the last batch
 *   otherwise);
 * - `pretend`: list the SQL statements instead of running them.
 */
class Migrator
{
    /** @var list<string> */
    protected array $notes = [];

    /** @var list<string> */
    protected array $paths = [];

    /**
     * Connection of the migrations that do not declare theirs (`--database`).
     */
    protected ?string $connection = null;

    /** @var array<string, MigrationInterface> */
    private array $resolved = [];

    public function __construct(protected StorageInterface $storage, protected PrefixInterface $prefix) {}

    /**
     * Connection (`db.<name>`) of the migrations that do not declare theirs: the default one when `null`.
     */
    public function setConnection(?string $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Run the pending migrations at the given paths.
     *
     * @param string|list<string>                     $paths
     * @param array{step?: bool|int, pretend?: bool}  $options
     *
     * @return list<string> The files of the migrations that ran
     */
    public function run(string|array $paths = [], array $options = []): array
    {
        $this->notes = [];

        $ran = array_flip($this->storage->getRan());
        $migrations = array_values(array_filter($this->getMigrationFiles($paths), fn(string $file): bool => !isset($ran[$this->getMigrationName($file)])));

        $this->runPending($migrations, $options);

        return $migrations;
    }

    /**
     * Run an array of migrations.
     *
     * @param list<string>                            $migrations Files
     * @param array{step?: bool|int, pretend?: bool}  $options
     */
    public function runPending(array $migrations, array $options = []): void
    {
        if ($migrations === []) {
            $this->note(Decorate::info('Nothing to migrate.'));

            return;
        }

        $batch = $this->storage->getNextBatchNumber();
        $step = (bool) ($options['step'] ?? false);
        $pretend = (bool) ($options['pretend'] ?? false);

        foreach ($migrations as $file) {
            $this->runUp($file, $batch, $pretend);

            if ($step) {
                $batch++;
            }
        }
    }

    /**
     * Rollback the last migration operation.
     *
     * @param string|list<string>                $paths
     * @param array{step?: int, pretend?: bool}  $options
     *
     * @return list<string> The files of the migrations rolled back
     */
    public function rollback(string|array $paths = [], array $options = []): array
    {
        $this->notes = [];

        $steps = (int) ($options['step'] ?? 0);
        $migrations = array_column($steps > 0 ? $this->storage->getMigrations($steps) : $this->storage->getLast(), 'migration');

        if ($migrations === []) {
            $this->note(Decorate::info('Nothing to rollback.'));

            return [];
        }

        return $this->rollbackMigrations($migrations, $paths, $options);
    }

    /**
     * Rolls all of the currently applied migrations back.
     *
     * @param string|list<string>   $paths
     * @param array{pretend?: bool} $options
     *
     * @return list<string> The files of the migrations rolled back
     */
    public function reset(string|array $paths = [], array $options = []): array
    {
        $this->notes = [];

        $migrations = array_reverse($this->storage->getRan());

        if ($migrations === []) {
            $this->note(Decorate::info('Nothing to rollback.'));

            return [];
        }

        return $this->rollbackMigrations($migrations, $paths, $options);
    }

    /**
     * Resolve the migration of a file: the instance it returns, or the class named after the file.
     */
    public function resolve(string $file): MigrationInterface
    {
        if (isset($this->resolved[$file])) {
            return $this->resolved[$file];
        }

        $class = Str::studly($this->prefix->deletePrefix($this->getMigrationName($file)));
        $migration = class_exists($class, false) ? null : self::load($file);

        if (!$migration instanceof MigrationInterface && class_exists($class, false)) {
            $migration = new $class();
        }

        if (!$migration instanceof MigrationInterface) {
            throw new RuntimeException("Migration $file: return an instance of " . Migration::class . " (`return new class extends Migration { … };`) or declare the class $class.");
        }

        return $this->resolved[$file] = $migration;
    }

    /**
     * The migration files of the given paths, by name, in their order.
     *
     * @param string|list<string> $paths
     *
     * @return array<string, string>
     */
    public function getMigrationFiles(string|array $paths): array
    {
        $migrations = [];

        foreach ((array) $paths as $path) {
            foreach (glob(rtrim($path, '/') . '/*_*.php') ?: [] as $file) {
                $migrations[$this->getMigrationName($file)] = $file;
            }
        }

        ksort($migrations, SORT_STRING);

        return $migrations;
    }

    /**
     * Load the migrations of the given files.
     *
     * @param array<string> $files
     */
    public function requireFiles(array $files): void
    {
        foreach ($files as $file) {
            $this->resolve($file);
        }
    }

    /**
     * Get the name of the migration.
     */
    public function getMigrationName(string $path): string
    {
        return basename($path, '.php');
    }

    /**
     * Register a custom migration path.
     */
    public function path(string $path): void
    {
        $this->paths = array_values(array_unique([...$this->paths, $path]));
    }

    /**
     * Get all of the custom migration paths.
     *
     * @return list<string>
     */
    public function paths(): array
    {
        return $this->paths;
    }

    public function getStorage(): StorageInterface
    {
        return $this->storage;
    }

    /**
     * Determine if the migration storage exists.
     */
    public function storageExist(): bool
    {
        return $this->storage->storageExist();
    }

    /**
     * Get the notes for the last operation.
     *
     * @return list<string>
     */
    public function getNotes(): array
    {
        return $this->notes;
    }

    /**
     * @param list<string>          $migrations Names, the first one rolled back first
     * @param string|list<string>   $paths
     * @param array{pretend?: bool} $options
     *
     * @return list<string>
     */
    protected function rollbackMigrations(array $migrations, string|array $paths, array $options): array
    {
        $files = $this->getMigrationFiles($paths);
        $pretend = (bool) ($options['pretend'] ?? false);
        $rolledBack = [];

        foreach ($migrations as $name) {
            if (!isset($files[$name])) {
                $this->note(Decorate::warn('Migration not found:') . " $name");

                continue;
            }

            $rolledBack[] = $files[$name];

            $this->runDown($files[$name], $pretend);
        }

        return $rolledBack;
    }

    protected function runUp(string $file, int $batch, bool $pretend): void
    {
        $migration = $this->resolve($file);
        $name = $this->getMigrationName($file);

        if ($pretend) {
            $this->pretendToRun($name, $migration, 'up');

            return;
        }

        $this->note(Decorate::notice('Migrating:') . " $name");

        $this->runMigration($migration, 'up', fn() => $this->storage->log($name, $batch));

        $this->note(Decorate::info('Migrated:') . "  $name");
    }

    protected function runDown(string $file, bool $pretend): void
    {
        $migration = $this->resolve($file);
        $name = $this->getMigrationName($file);

        if ($pretend) {
            $this->pretendToRun($name, $migration, 'down');

            return;
        }

        $this->note(Decorate::notice('Rolling back:') . " $name");

        $this->runMigration($migration, 'down', fn() => $this->storage->delete($name));

        $this->note(Decorate::info('Rolled back:') . "  $name");
    }

    /**
     * Runs a migration, then logs it, in a transaction when its database can roll back schema changes.
     *
     * @param 'up'|'down'     $method
     * @param \Closure(): void $log
     */
    protected function runMigration(MigrationInterface $migration, string $method, \Closure $log): void
    {
        $schema = new Builder(Db::connection($this->connectionOf($migration)));
        $db = $schema->connection();
        $transaction = (!$migration instanceof Migration || $migration->withinTransaction()) && $schema->grammar()->supportsSchemaTransactions();

        if (!$transaction) {
            $migration->{$method}($schema);
            $log();

            return;
        }

        $db->begin();

        try {
            $migration->{$method}($schema);
            $log();

            $db->commit();
        } catch (Throwable $e) {
            if ($db->isUnderTransaction()) {
                $db->rollback();
            }

            throw $e;
        }
    }

    /**
     * Notes the SQL statements of a migration, without running them.
     *
     * @param 'up'|'down' $method
     */
    protected function pretendToRun(string $name, MigrationInterface $migration, string $method): void
    {
        $connection = $this->connectionOf($migration);

        $this->note(Decorate::info($name) . ':');

        $queries = Db::pretend(function () use ($migration, $method, $connection): void {
            $migration->{$method}(new Builder(Db::connection($connection)));
        }, $connection, true);

        foreach ($queries as $query) {
            $this->note($this->highlight($query));
        }

        $this->note('');
    }

    /**
     * Raise a note event for the migrator.
     */
    protected function note(string $message): void
    {
        $this->notes[] = $message;
    }

    private function connectionOf(MigrationInterface $migration): ?string
    {
        return ($migration instanceof Migration ? $migration->getConnection() : null) ?? $this->connection;
    }

    /**
     * Colored SQL, with `tempest/highlight` (optional).
     */
    private function highlight(string $sql): string
    {
        if (!Decorate::hasColorSupport() || !class_exists(Highlighter::class)) {
            return $sql;
        }

        return (new Highlighter(new LightTerminalTheme()))->parse($sql, 'sql');
    }

    /**
     * Requires a migration file, isolated from the migrator.
     */
    private static function load(string $file): mixed
    {
        return require $file;
    }
}

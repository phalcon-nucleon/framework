<?php

declare(strict_types=1);

namespace Neutrino\Database\Migrations;

use InvalidArgumentException;
use Neutrino\Database\Migrations\Prefix\PrefixInterface;
use RuntimeException;

/**
 * Writes a new migration file, returning an anonymous class (stubs `blank`, `create`, `update`).
 */
class MigrationCreator
{
    public function __construct(protected PrefixInterface $prefix) {}

    /**
     * Create a new migration at the given path.
     *
     * @param bool $create `true`: creates `$table`; `false`: modifies it
     *
     * @return string The path of the file
     */
    public function create(string $name, string $path, ?string $table = null, bool $create = false): string
    {
        if (preg_match('/^\w+$/', $name) !== 1) {
            throw new InvalidArgumentException("Invalid migration name \"$name\": letters, digits and underscores only.");
        }

        $this->ensureMigrationDoesntAlreadyExist($name, $path);

        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException("Cannot create the directory $path.");
        }

        $file = $this->getPath($name, $path);

        if (file_put_contents($file, $this->populateStub($this->getStubContent($table, $create), $table)) === false) {
            throw new RuntimeException("Cannot write $file.");
        }

        return $file;
    }

    /**
     * Get the path to the stubs.
     */
    public function stubsPath(): string
    {
        return __DIR__ . '/stubs';
    }

    /**
     * Ensure that a migration with the given name doesn't already exist.
     *
     * @throws InvalidArgumentException
     */
    protected function ensureMigrationDoesntAlreadyExist(string $name, string $path): void
    {
        foreach (glob(rtrim($path, '/') . '/*_*.php') ?: [] as $file) {
            if (strcasecmp($this->prefix->deletePrefix(basename($file, '.php')), $name) === 0) {
                throw new InvalidArgumentException("A migration \"$name\" already exists: " . basename($file) . '.');
            }
        }
    }

    protected function getStubContent(?string $table, bool $create): string
    {
        $stub = $table === null ? 'blank' : ($create ? 'create' : 'update');

        return (string) file_get_contents($this->stubsPath() . "/$stub.stub");
    }

    protected function populateStub(string $stub, ?string $table): string
    {
        return $table === null ? $stub : str_replace('{table}', str_replace(['\\', "'"], ['\\\\', "\\'"], $table), $stub);
    }

    protected function getPath(string $name, string $path): string
    {
        return rtrim($path, '/') . '/' . $this->prefix->getPrefix() . '_' . $name . '.php';
    }
}

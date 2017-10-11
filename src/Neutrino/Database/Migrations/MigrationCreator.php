<?php

namespace Neutrino\Database\Migrations;

use Neutrino\Database\Migrations\Prefix\PrefixInterface;
use Neutrino\Support\Str;
use InvalidArgumentException;

/**
 * Class MigrationCreator
 *
 * @package Neutrino\Database\Migrations
 */
class MigrationCreator
{
    /**
     * @var \Neutrino\Database\Migrations\Prefix\PrefixInterface
     */
    protected $prefix;

    /**
     * MigrationCreator constructor.
     *
     * @param $prefix
     */
    public function __construct(PrefixInterface $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Create a new migration at the given path.
     *
     * @param  string $name
     * @param  string $path
     * @param  string $table
     * @param  bool   $create
     *
     * @return string
     * @throws \Exception
     */
    public function create($name, $path, $table = null, $create = false)
    {
        $this->ensureMigrationDoesntAlreadyExist($name);

        $stub = $this->getStubContent($table, $create);

        $populatedStub = $this->populateStub($name, $stub, $table);

        $path = $this->getPath($name, $path);

        file_put_contents($path, $populatedStub);

        return $path;
    }

    /**
     * Ensure that a migration with the given name doesn't already exist.
     *
     * @param  string $name
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function ensureMigrationDoesntAlreadyExist($name)
    {
        if (class_exists($className = $this->getClassName($name))) {
            throw new InvalidArgumentException("A {$className} class already exists.");
        }
    }

    /**
     * Get the migration stub file.
     *
     * @param  string $table
     * @param  bool   $create
     *
     * @return string
     */
    protected function getStubContent($table, $create)
    {
        if (is_null($table)) {
            $file = $this->stubsPath() . '/blank.stub';
        } else {
            $stub = $create ? 'create.stub' : 'update.stub';

            $file = $this->stubsPath() . '/' . $stub;
        }

        return file_get_contents($file);
    }

    /**
     * Populate the place-holders in the migration stub.
     *
     * @param  string $name
     * @param  string $stub
     * @param  string $table
     *
     * @return string
     */
    protected function populateStub($name, $stub, $table)
    {
        $stub = str_replace('{class}', $this->getClassName($name), $stub);

        if (!is_null($table)) {
            $stub = str_replace('{table}', $table, $stub);
        }

        return $stub;
    }

    /**
     * Get the class name of a migration name.
     *
     * @param  string $name
     *
     * @return string
     */
    protected function getClassName($name)
    {
        return Str::studly($name);
    }

    /**
     * Get the full path to the migration.
     *
     * @param  string $name
     * @param  string $path
     *
     * @return string
     */
    protected function getPath($name, $path)
    {
        if (!is_null($prefix = $this->prefix->getPrefix())) {
            $prefix .= '_';
        }

        return $path . '/' . $prefix . $name . '.php';
    }

    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubsPath()
    {
        return __DIR__ . '/stubs';
    }
}

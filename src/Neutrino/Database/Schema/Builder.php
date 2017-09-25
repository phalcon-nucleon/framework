<?php

namespace Neutrino\Database\Schema;

use Closure;
use LogicException;
use Neutrino\Support\Func;
use Phalcon\Di\Injectable;

class Builder extends Injectable
{
    /**
     * The database configuration.
     *
     * @var array
     */
    protected $dbConfig;

    /**
     * The schema grammar instance.
     *
     * @var \Neutrino\Database\Schema\DialectInterface
     */
    protected $grammar;

    /**
     * The Blueprint resolver callback.
     *
     * @var \Closure
     */
    protected $resolver;

    /**
     * The default string length for migrations.
     *
     * @var int
     */
    public static $defaultStringLength = 255;

    /**
     * Create a new database Schema manager.
     */
    public function __construct()
    {
        $this->dbConfig = $this->db->getDescriptor();

        $this->grammar = Dialect\Factory::create($this->db->getDialect());
    }

    /**
     * Set the default string length for migrations.
     *
     * @param  int $length
     *
     * @return void
     */
    public static function defaultStringLength($length)
    {
        static::$defaultStringLength = $length;
    }

    /**
     * Determine if the given table exists.
     *
     * @param  string $table
     *
     * @return bool
     */
    public function hasTable($table)
    {
        return $this->db->tableExists($table);
    }

    /**
     * Determine if the given table has a given column.
     *
     * @param string $table
     * @param string $column
     *
     * @return bool
     */
    public function hasColumn($table, $column)
    {
        $tableColumns = array_map('strtolower', $this->listColumnsName($table));

        return in_array(strtolower($column), $tableColumns);
    }

    /**
     * Determine if the given table has given columns.
     *
     * @param  string $table
     * @param  array  $columns
     *
     * @return bool
     */
    public function hasColumns($table, array $columns)
    {
        $tableColumns = array_map('strtolower', $this->listColumnsName($table));

        foreach ($columns as $column) {
            if (!in_array(strtolower($column), $tableColumns)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the data type for the given column name.
     *
     * @param string $table
     * @param string $column
     * @param string $schema
     *
     * @return string
     */
    public function getColumnType($table, $column, $schema = null)
    {
        $col = $this->describeColumn($table, $column, $schema);

        if (!is_null($col)) {
            return $col->getType();
        }

        return null;
    }

    /**
     * Get the column listing for a given table.
     *
     * @param  string $table
     *
     * @return array
     */
    public function getColumnListing($table)
    {
        return $this->db->describeColumns($table, $this->dbConfig['dbname']);
    }

    /**
     * Modify a table on the schema.
     *
     * @param  string   $table
     * @param  \Closure $callback
     *
     * @return void
     */
    public function table($table, Closure $callback)
    {
        $this->build(Func::tap($this->createBlueprint($table), function (Blueprint $blueprint) use ($callback) {
            $blueprint->update();

            $callback($blueprint);
        }));
    }

    /**
     * Create a new table on the schema.
     *
     * @param  string   $table
     * @param  \Closure $callback
     *
     * @return void
     */
    public function create($table, Closure $callback)
    {
        $this->build(Func::tap($this->createBlueprint($table), function (Blueprint $blueprint) use ($callback) {
            $blueprint->create();

            $callback($blueprint);
        }));
    }

    /**
     * Drop a table from the schema.
     *
     * @param  string $table
     *
     * @return void
     */
    public function drop($table)
    {
        $this->build(Func::tap($this->createBlueprint($table), function (Blueprint $blueprint) {
            $blueprint->drop();
        }));
    }

    /**
     * Drop a table from the schema if it exists.
     *
     * @param  string $table
     *
     * @return void
     */
    public function dropIfExists($table)
    {
        $this->build(Func::tap($this->createBlueprint($table), function (Blueprint $blueprint) {
            $blueprint->dropIfExists();
        }));
    }

    /**
     * Drop all tables from the database.
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function dropAllTables()
    {
        throw new LogicException('This database driver does not support dropping all tables.');
    }

    /**
     * Rename a table on the schema.
     *
     * @param  string $from
     * @param  string $to
     *
     * @return void
     */
    public function rename($from, $to)
    {
        $this->build(Func::tap($this->createBlueprint($from), function (Blueprint $blueprint) use ($to) {
            $blueprint->rename($to);
        }));
    }

    /**
     * Enable foreign key constraints.
     *
     * @return bool
     */
    public function enableForeignKeyConstraints()
    {
        return $this->db->execute(
            $this->grammar->enableForeignKeyConstraints()
        );
    }

    /**
     * Disable foreign key constraints.
     *
     * @return bool
     */
    public function disableForeignKeyConstraints()
    {
        return $this->db->execute(
            $this->grammar->disableForeignKeyConstraints()
        );
    }

    /**
     * Execute the blueprint to build / modify the table.
     *
     * @param  \Neutrino\Database\Schema\Blueprint $blueprint
     *
     * @return void
     */
    protected function build(Blueprint $blueprint)
    {
        $blueprint->build($this->db, $this->dbConfig, $this->grammar);
    }

    /**
     * Create a new command set with a Closure.
     *
     * @param  string        $table
     * @param  \Closure|null $callback
     *
     * @return \Neutrino\Database\Schema\Blueprint
     */
    protected function createBlueprint($table, Closure $callback = null)
    {
        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $table, $callback);
        }

        return new Blueprint($table, $callback);
    }

    /**
     * @param string $table
     * @param string $column
     * @param null   $schema
     *
     * @return null|\Phalcon\Db\ColumnInterface
     */
    protected function describeColumn($table, $column, $schema = null)
    {
        foreach ($this->db->describeColumns($table, $schema) as $col) {
            if ($col->getName() == $column) {
                return $col;
            }
        }

        return null;
    }

    /**
     * @param string                             $table
     * @param \Phalcon\Db\ColumnInterface[]|null $tableColumns
     *
     * @return array
     */
    protected function listColumnsName($table, array $tableColumns = null)
    {
        if (is_null($tableColumns)) {
            $tableColumns = $this->getColumnListing($table);
        }

        $columnsName = [];

        foreach ($tableColumns as $tableColumn) {
            $columnsName[] = $tableColumn->getName();
        }

        return $columnsName;
    }

    /**
     * Set the Schema Blueprint resolver callback.
     *
     * @param  \Closure $resolver
     *
     * @return void
     */
    public function blueprintResolver(Closure $resolver)
    {
        $this->resolver = $resolver;
    }
}

<?php

namespace Neutrino\Database\Schema\Dialect;

use Phalcon\Db;

/**
 * trait WrapperTrait
 *
 * @package Neutrino\Database\Schema\Dialect
 */
trait WrapperTrait
{
    /** @var \Phalcon\Db\DialectInterface */
    protected $dialect;

    /**
     * WrapperTrait constructor.
     *
     * @param \Phalcon\Db\DialectInterface $dialect
     */
    public function __construct(Db\DialectInterface $dialect)
    {
        $this->dialect = $dialect;
    }

    /**
     * Generates the SQL for LIMIT clause
     *
     * @param string $sqlQuery
     * @param mixed  $number
     *
     * @return string
     */
    public function limit($sqlQuery, $number)
    {
        return $this->dialect->limit($sqlQuery, $number);
    }

    /**
     * Returns a SQL modified with a FOR UPDATE clause
     *
     * @param string $sqlQuery
     *
     * @return string
     */
    public function forUpdate($sqlQuery)
    {
        return $this->dialect->forUpdate($sqlQuery);
    }

    /**
     * Returns a SQL modified with a LOCK IN SHARE MODE clause
     *
     * @param string $sqlQuery
     *
     * @return string
     */
    public function sharedLock($sqlQuery)
    {
        return $this->dialect->sharedLock($sqlQuery);
    }

    /**
     * Builds a SELECT statement
     *
     * @param array $definition
     *
     * @return string
     */
    public function select(array $definition)
    {
        return $this->dialect->select($definition);
    }

    /**
     * Checks whether the platform supports savepoints
     *
     * @return bool
     */
    public function supportsSavepoints()
    {
        return $this->dialect->supportsSavepoints();
    }

    /**
     * Checks whether the platform supports releasing savepoints.
     *
     * @return bool
     */
    public function supportsReleaseSavepoints()
    {
        return $this->dialect->supportsReleaseSavepoints();
    }

    /**
     * Generate SQL to create a new savepoint
     *
     * @param string $name
     *
     * @return string
     */
    public function createSavepoint($name)
    {
        return $this->dialect->createSavepoint($name);
    }

    /**
     * Generate SQL to release a savepoint
     *
     * @param string $name
     *
     * @return string
     */
    public function releaseSavepoint($name)
    {
        return $this->dialect->releaseSavepoint($name);
    }

    /**
     * Generate SQL to rollback a savepoint
     *
     * @param string $name
     *
     * @return string
     */
    public function rollbackSavepoint($name)
    {
        return $this->dialect->rollbackSavepoint($name);
    }

    /**
     * Gets the column name in RDBMS
     *
     * @param \Phalcon\Db\ColumnInterface $column
     *
     * @return string
     */
    public function getColumnDefinition(Db\ColumnInterface $column)
    {
        return $this->dialect->getColumnDefinition($column);
    }

    /**
     * Generates SQL to add a column to a table
     *
     * @param string                      $tableName
     * @param string                      $schemaName
     * @param \Phalcon\Db\ColumnInterface $column
     *
     * @return string
     */
    public function addColumn($tableName, $schemaName, Db\ColumnInterface $column)
    {
        return $this->dialect->addColumn($tableName, $schemaName, $column);
    }

    /**
     * Generates SQL to modify a column in a table
     *
     * @param string                      $tableName
     * @param string                      $schemaName
     * @param \Phalcon\Db\ColumnInterface $column
     * @param \Phalcon\Db\ColumnInterface $currentColumn
     *
     * @return string
     */
    public function modifyColumn(
        $tableName,
        $schemaName,
        Db\ColumnInterface $column,
        Db\ColumnInterface $currentColumn = null
    )
    {
        return $this->dialect->modifyColumn($tableName, $schemaName, $column, $currentColumn);
    }

    /**
     * Generates SQL to delete a column from a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param string $columnName
     *
     * @return string
     */
    public function dropColumn($tableName, $schemaName, $columnName)
    {
        return $this->dialect->dropColumn($tableName, $schemaName, $columnName);
    }

    /**
     * Generates SQL to add an index to a table
     *
     * @param string                     $tableName
     * @param string                     $schemaName
     * @param \Phalcon\Db\IndexInterface $index
     *
     * @return string
     */
    public function addIndex($tableName, $schemaName, Db\IndexInterface $index)
    {
        return $this->dialect->addIndex($tableName, $schemaName, $index);
    }

    /**
     * Generates SQL to delete an index from a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param string $indexName
     *
     * @return string
     */
    public function dropIndex($tableName, $schemaName, $indexName)
    {
        return $this->dialect->dropIndex($tableName, $schemaName, $indexName);
    }

    /**
     * Generates SQL to add the primary key to a table
     *
     * @param string                     $tableName
     * @param string                     $schemaName
     * @param \Phalcon\Db\IndexInterface $index
     *
     * @return string
     */
    public function addPrimaryKey($tableName, $schemaName, Db\IndexInterface $index)
    {
        return $this->dialect->addPrimaryKey($tableName, $schemaName, $index);
    }

    /**
     * Generates SQL to delete primary key from a table
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return string
     */
    public function dropPrimaryKey($tableName, $schemaName)
    {
        return $this->dialect->dropPrimaryKey($tableName, $schemaName);
    }

    /**
     * Generates SQL to add an index to a table
     *
     * @param string                         $tableName
     * @param string                         $schemaName
     * @param \Phalcon\Db\ReferenceInterface $reference
     *
     * @return string
     */
    public function addForeignKey($tableName, $schemaName, Db\ReferenceInterface $reference)
    {
        return $this->dialect->addForeignKey($tableName, $schemaName, $reference);
    }

    /**
     * Generates SQL to delete a foreign key from a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param string $referenceName
     *
     * @return string
     */
    public function dropForeignKey($tableName, $schemaName, $referenceName)
    {
        return $this->dialect->dropForeignKey($tableName, $schemaName, $referenceName);
    }

    /**
     * Generates SQL to create a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param array  $definition
     *
     * @return string
     */
    public function createTable($tableName, $schemaName, array $definition)
    {
        return $this->dialect->createTable($tableName, $schemaName, $definition);
    }

    /**
     * Generates SQL to create a view
     *
     * @param string $viewName
     * @param array  $definition
     * @param string $schemaName
     *
     * @return string
     */
    public function createView($viewName, array $definition, $schemaName = null)
    {
        return $this->dialect->createView($viewName, $definition, $schemaName);
    }

    /**
     * Generates SQL to drop a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param bool   $ifExists
     *
     * @return string
     */
    public function dropTable($tableName, $schemaName = null, $ifExists = null)
    {
        return $this->dialect->dropTable($tableName, $schemaName, $ifExists);
    }

    /**
     * Generates SQL to drop a view
     *
     * @param string $viewName
     * @param string $schemaName
     * @param bool   $ifExists
     *
     * @return string
     */
    public function dropView($viewName, $schemaName = null, $ifExists = true)
    {
        return $this->dialect->dropView($viewName, $schemaName, $ifExists);
    }

    /**
     * Generates SQL checking for the existence of a schema.table
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return string
     */
    public function tableExists($tableName, $schemaName = null)
    {
        return $this->dialect->tableExists($tableName, $schemaName);
    }

    /**
     * Generates SQL checking for the existence of a schema.view
     *
     * @param string $viewName
     * @param string $schemaName
     *
     * @return string
     */
    public function viewExists($viewName, $schemaName = null)
    {
        return $this->dialect->viewExists($viewName, $schemaName);
    }

    /**
     * Generates SQL to describe a table
     *
     * @param string $table
     * @param string $schema
     *
     * @return string
     */
    public function describeColumns($table, $schema = null)
    {
        return $this->dialect->describeColumns($table, $schema);
    }

    /**
     * List all tables in database
     *
     * @param string $schemaName
     *
     * @return string
     */
    public function listTables($schemaName = null)
    {
        return $this->dialect->listTables($schemaName);
    }

    /**
     * Generates SQL to query indexes on a table
     *
     * @param string $table
     * @param string $schema
     *
     * @return string
     */
    public function describeIndexes($table, $schema = null)
    {
        return $this->dialect->describeIndexes($table, $schema);
    }

    /**
     * Generates SQL to query foreign keys on a table
     *
     * @param string $table
     * @param string $schema
     *
     * @return string
     */
    public function describeReferences($table, $schema = null)
    {
        return $this->dialect->describeReferences($table, $schema);
    }

    /**
     * Generates the SQL to describe the table creation options
     *
     * @param string $table
     * @param string $schema
     *
     * @return string
     */
    public function tableOptions($table, $schema = null)
    {
        return $this->dialect->tableOptions($table, $schema);
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->dialect, $name)) {
            return $this->dialect->$name(...$arguments);
        }

        throw new \BadMethodCallException(
            'Method "' . $name . '" doesn\'t exist in "' . get_class($this->dialect) . '"'
        );
    }
}

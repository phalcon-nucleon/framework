<?php

namespace Neutrino\Database;

use Neutrino\Constants\Services;
use Neutrino\Support\DesignPatterns\Strategy;
use Phalcon\Db\AdapterInterface;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\DialectInterface;
use Phalcon\Db\IndexInterface;
use Phalcon\Db\RawValue;
use Phalcon\Db\ReferenceInterface;
use Phalcon\Db\ResultInterface;
use Phalcon\Di;

class DatabaseStrategy implements Strategy\StrategyInterface, AdapterInterface
{
    use Strategy\StrategyTrait;

    protected $_di;

    /**
     * CacheStrategy constructor.
     */
    public function __construct()
    {
        $database = $this->di(Services::CONFIG)->database;

        $this->default = $database->default;

        $this->supported = array_keys((array)$database->connections);
    }

    /**
     * @inheritdoc
     */
    protected function make($use)
    {
        return $this->di(Services::DB . '.' . $use);
    }

    protected function di($service)
    {
        if (isset($this->$service)) {
            return $this->$service;
        }

        if (!isset($this->_di)) {
            $this->_di = Di::getDefault();
        }

        return $this->$service = $this->_di->getShared($service);
    }

    public function __call($name, $arguments)
    {
        return $this->uses()->$name(...$arguments);
    }

    /**
     * This method is automatically called in \Phalcon\Db\Adapter\Pdo constructor.
     * Call it when you need to restore a database connection
     *
     * @param array $descriptor
     * @param array $args
     * @return bool
     */
    public function connect(array $descriptor = null, ...$args)
    {
        return $this->uses()->connect($descriptor, ...$args);
    }

    /**
     * Sends SQL statements to the database server returning the success state.
     * Use this method only when the SQL statement sent to the server return rows
     *
     * @param string $sqlStatement
     * @param mixed $placeholders
     * @param mixed $dataTypes
     * @param array $args
     * @return bool|ResultInterface
     */
    public function query($sqlStatement, $placeholders = null, $dataTypes = null, ...$args)
    {
        return $this->uses()->query($sqlStatement, $placeholders, $dataTypes, ...$args);
    }

    /**
     * Sends SQL statements to the database server returning the success state.
     * Use this method only when the SQL statement sent to the server doesn't return any rows
     *
     * @param string $sqlStatement
     * @param mixed $placeholders
     * @param mixed $dataTypes
     * @param array $args
     * @return bool
     */
    public function execute($sqlStatement, $placeholders = null, $dataTypes = null, ...$args)
    {
        return $this->uses()->execute($sqlStatement, $placeholders, $dataTypes, ...$args);
    }

    /**
     * Returns the number of affected rows by the last INSERT/UPDATE/DELETE reported by the database system
     *
     * @param array $args
     * @return int
     */
    public function affectedRows(...$args)
    {
        return $this->uses()->affectedRows(...$args);
    }

    /**
     * Closes active connection returning success. Phalcon automatically closes
     * and destroys active connections within Phalcon\Db\Pool
     *
     * @param array $args
     * @return bool
     */
    public function close(...$args)
    {
        return $this->uses()->close(...$args);
    }

    /**
     * Escapes a value to avoid SQL injections
     *
     * @param string $str
     * @param array $args
     * @return string
     */
    public function escapeString($str, ...$args)
    {
        return $this->uses()->escapeString($str, ...$args);
    }

    /**
     * Returns insert id for the auto_increment column inserted in the last SQL statement
     *
     * @param string $sequenceName
     * @param array $args
     * @return int
     */
    public function lastInsertId($sequenceName = null, ...$args)
    {
        return $this->uses()->lastInsertId($sequenceName, ...$args);
    }

    /**
     * Starts a transaction in the connection
     *
     * @param bool $nesting
     * @param array $args
     * @return bool
     */
    public function begin($nesting = true, ...$args)
    {
        return $this->uses()->begin($nesting, ...$args);
    }

    /**
     * Rollbacks the active transaction in the connection
     *
     * @param bool $nesting
     * @param array $args
     * @return bool
     */
    public function rollback($nesting = true, ...$args)
    {
        return $this->uses()->rollback($nesting, ...$args);
    }

    /**
     * Commits the active transaction in the connection
     *
     * @param bool $nesting
     * @param array $args
     * @return bool
     */
    public function commit($nesting = true, ...$args)
    {
        return $this->uses()->commit($nesting, ...$args);
    }

    /**
     * Checks whether connection is under database transaction
     *
     * @param array $args
     * @return bool
     */
    public function isUnderTransaction(...$args)
    {
        return $this->uses()->isUnderTransaction(...$args);
    }

    /**
     * Return internal PDO handler
     *
     * @param array $args
     * @return \Pdo
     */
    public function getInternalHandler(...$args)
    {
        return $this->uses()->getInternalHandler(...$args);
    }

    /**
     * Returns an array of Phalcon\Db\Column objects describing a table
     *
     * @param string $table
     * @param string $schema
     * @param array $args
     * @return ColumnInterface[]
     */
    public function describeColumns($table, $schema = null, ...$args)
    {
        return $this->uses()->describeColumns($table, $schema, ...$args);
    }

    /**
     * Returns the first row in a SQL query result
     *
     * @param string $sqlQuery
     * @param int $fetchMode
     * @param int $placeholders
     * @param array $args
     * @return array
     */
    public function fetchOne($sqlQuery, $fetchMode = 2, $placeholders = null, ...$args)
    {
        return $this->uses()->fetchOne($sqlQuery, $fetchMode, $placeholders, ...$args);
    }

    /**
     * Dumps the complete result of a query into an array
     *
     * @param string $sqlQuery
     * @param int $fetchMode
     * @param int $placeholders
     * @param array $args
     * @return array
     */
    public function fetchAll($sqlQuery, $fetchMode = 2, $placeholders = null, ...$args)
    {
        return $this->uses()->fetchAll($sqlQuery, $fetchMode, $placeholders, ...$args);
    }

    /**
     * Inserts data into a table using custom RDBMS SQL syntax
     *
     * @param mixed $table
     * @param array $values
     * @param mixed $fields
     * @param mixed $dataTypes
     * @param array $args
     * @return mixed
     */
    public function insert($table, array $values, $fields = null, $dataTypes = null, ...$args)
    {
        return $this->uses()->insert($table, $values, $fields, $dataTypes, ...$args); }

    /**
     * Updates data on a table using custom RDBMS SQL syntax
     *
     * @param mixed $table
     * @param mixed $fields
     * @param mixed $values
     * @param mixed $whereCondition
     * @param mixed $dataTypes
     * @param array $args
     * @return mixed
     */
    public function update($table, $fields, $values, $whereCondition = null, $dataTypes = null, ...$args)
    {
        return $this->uses()->update($table, $fields, $values, $whereCondition, $dataTypes, ...$args);
    }

    /**
     * Deletes data from a table using custom RDBMS SQL syntax
     *
     * @param string $table
     * @param string $whereCondition
     * @param array $placeholders
     * @param array $dataTypes
     * @param array $args
     * @return boolean
     */
    public function delete($table, $whereCondition = null, $placeholders = null, $dataTypes = null, ...$args)
    {
        return $this->uses()->delete($table, $whereCondition, $placeholders, $dataTypes, ...$args);
    }

    /**
     * Gets a list of columns
     *
     * @param mixed $columnList
     * @param array $args
     * @return    string
     */
    public function getColumnList($columnList, ...$args)
    {
        return $this->uses()->getColumnList($columnList, ...$args);
    }

    /**
     * Appends a LIMIT clause to sqlQuery argument
     *
     * @param mixed $sqlQuery
     * @param mixed $number
     * @param array $args
     * @return mixed
     */
    public function limit($sqlQuery, $number, ...$args)
    {
        return $this->uses()->limit($sqlQuery, $number, ...$args);
    }

    /**
     * Generates SQL checking for the existence of a schema.table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param array $args
     * @return bool
     */
    public function tableExists($tableName, $schemaName = null, ...$args)
    {
        return $this->uses()->tableExists($tableName, $schemaName, ...$args);
    }

    /**
     * Generates SQL checking for the existence of a schema.view
     *
     * @param string $viewName
     * @param string $schemaName
     * @param array $args
     * @return bool
     */
    public function viewExists($viewName, $schemaName = null, ...$args)
    {
        return $this->uses()->viewExists($viewName, $schemaName, ...$args);
    }

    /**
     * Returns a SQL modified with a FOR UPDATE clause
     *
     * @param string $sqlQuery
     * @param array $args
     * @return string
     */
    public function forUpdate($sqlQuery, ...$args)
    {
        return $this->uses()->forUpdate($sqlQuery, ...$args);
    }

    /**
     * Returns a SQL modified with a LOCK IN SHARE MODE clause
     *
     * @param string $sqlQuery
     * @param array $args
     * @return string
     */
    public function sharedLock($sqlQuery, ...$args)
    {
        return $this->uses()->sharedLock($sqlQuery, ...$args);
    }

    /**
     * Creates a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param array $definition
     * @param array $args
     * @return bool
     */
    public function createTable($tableName, $schemaName, array $definition, ...$args)
    {
        return $this->uses()->createTable($tableName, $schemaName, $definition, ...$args); }

    /**
     * Drops a table from a schema/database
     *
     * @param string $tableName
     * @param string $schemaName
     * @param bool $ifExists
     * @param array $args
     * @return bool
     */
    public function dropTable($tableName, $schemaName = null, $ifExists = true, ...$args)
    {
        return $this->uses()->dropTable($tableName, $schemaName, $ifExists, ...$args);
    }

    /**
     * Creates a view
     *
     * @param string $viewName
     * @param array $definition
     * @param string $schemaName
     * @param array $args
     * @return bool
     */
    public function createView($viewName, array $definition, $schemaName = null, ...$args)
    {
        return $this->uses()->createView($viewName, $definition, $schemaName, ...$args); }

    /**
     * Drops a view
     *
     * @param string $viewName
     * @param string $schemaName
     * @param bool $ifExists
     * @param array $args
     * @return bool
     */
    public function dropView($viewName, $schemaName = null, $ifExists = true, ...$args)
    {
        return $this->uses()->dropView($viewName, $schemaName, $ifExists, ...$args);
    }

    /**
     * Adds a column to a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param ColumnInterface $column
     * @param array $args
     * @return bool
     */
    public function addColumn($tableName, $schemaName, ColumnInterface $column, ...$args)
    {
        return $this->uses()->addColumn($tableName, $schemaName, $column, ...$args); }

    /**
     * Modifies a table column based on a definition
     *
     * @param string $tableName
     * @param string $schemaName
     * @param ColumnInterface $column
     * @param ColumnInterface $currentColumn
     * @param array $args
     * @return bool
     */
    public function modifyColumn($tableName, $schemaName, ColumnInterface $column, ColumnInterface $currentColumn = null, ...$args)
    {
        return $this->uses()->modifyColumn($tableName, $schemaName, $column, $currentColumn, ...$args); }

    /**
     * Drops a column from a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param string $columnName
     * @param array $args
     * @return bool
     */
    public function dropColumn($tableName, $schemaName, $columnName, ...$args)
    {
        return $this->uses()->dropColumn($tableName, $schemaName, $columnName, ...$args);
    }

    /**
     * Adds an index to a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param IndexInterface $index
     * @param array $args
     * @return bool
     */
    public function addIndex($tableName, $schemaName, IndexInterface $index, ...$args)
    {
        return $this->uses()->addIndex($tableName, $schemaName, $index, ...$args); }

    /**
     * Drop an index from a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param string $indexName
     * @param array $args
     * @return bool
     */
    public function dropIndex($tableName, $schemaName, $indexName, ...$args)
    {
        return $this->uses()->dropIndex($tableName, $schemaName, $indexName, ...$args);
    }

    /**
     * Adds a primary key to a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param IndexInterface $index
     * @param array $args
     * @return bool
     */
    public function addPrimaryKey($tableName, $schemaName, IndexInterface $index, ...$args)
    {
        return $this->uses()->addPrimaryKey($tableName, $schemaName, $index, ...$args); }

    /**
     * Drops primary key from a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param array $args
     * @return bool
     */
    public function dropPrimaryKey($tableName, $schemaName, ...$args)
    {
        return $this->uses()->dropPrimaryKey($tableName, $schemaName, ...$args);
    }

    /**
     * Adds a foreign key to a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param ReferenceInterface $reference
     * @param array $args
     * @return bool
     */
    public function addForeignKey($tableName, $schemaName, ReferenceInterface $reference, ...$args)
    {
        return $this->uses()->addForeignKey($tableName, $schemaName, $reference, ...$args); }

    /**
     * Drops a foreign key from a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param string $referenceName
     * @param array $args
     * @return bool
     */
    public function dropForeignKey($tableName, $schemaName, $referenceName, ...$args)
    {
        return $this->uses()->dropForeignKey($tableName, $schemaName, $referenceName, ...$args);
    }

    /**
     * Returns the SQL column definition from a column
     *
     * @param ColumnInterface $column
     * @param array $args
     * @return string
     */
    public function getColumnDefinition(ColumnInterface $column, ...$args)
    {
        return $this->uses()->getColumnDefinition($column, ...$args); }

    /**
     * List all tables on a database
     *
     * @param string $schemaName
     * @param array $args
     * @return array
     */
    public function listTables($schemaName = null, ...$args)
    {
        return $this->uses()->listTables($schemaName, ...$args);
    }

    /**
     * List all views on a database
     *
     * @param string $schemaName
     * @param array $args
     * @return array
     */
    public function listViews($schemaName = null, ...$args)
    {
        return $this->uses()->listViews($schemaName, ...$args);
    }

    /**
     * Return descriptor used to connect to the active database
     *
     * @param array $args
     * @return array
     */
    public function getDescriptor(...$args)
    {
        return $this->uses()->getDescriptor(...$args);
    }

    /**
     * Gets the active connection unique identifier
     *
     * @param array $args
     * @return string
     */
    public function getConnectionId(...$args)
    {
        return $this->uses()->getConnectionId(...$args);
    }

    /**
     * Active SQL statement in the object
     *
     * @param array $args
     * @return string
     */
    public function getSQLStatement(...$args)
    {
        return $this->uses()->getSQLStatement(...$args);
    }

    /**
     * Active SQL statement in the object without replace bound parameters
     *
     * @param array $args
     * @return string
     */
    public function getRealSQLStatement(...$args)
    {
        return $this->uses()->getRealSQLStatement(...$args);
    }

    /**
     * Active SQL statement in the object
     *
     * @param array $args
     * @return array
     */
    public function getSQLVariables(...$args)
    {
        return $this->uses()->getSQLVariables(...$args);
    }

    /**
     * Active SQL statement in the object
     *
     * @param array $args
     * @return array
     */
    public function getSQLBindTypes(...$args)
    {
        return $this->uses()->getSQLBindTypes(...$args);
    }

    /**
     * Returns type of database system the adapter is used for
     *
     * @param array $args
     * @return string
     */
    public function getType(...$args)
    {
        return $this->uses()->getType(...$args);
    }

    /**
     * Returns the name of the dialect used
     *
     * @param array $args
     * @return string
     */
    public function getDialectType(...$args)
    {
        return $this->uses()->getDialectType(...$args);
    }

    /**
     * Returns internal dialect instance
     *
     * @param array $args
     * @return DialectInterface
     */
    public function getDialect(...$args)
    {
        return $this->uses()->getDialect(...$args);
    }

    /**
     * Escapes a column/table/schema name
     *
     * @param string $identifier
     * @param array $args
     * @return string
     */
    public function escapeIdentifier($identifier, ...$args)
    {
        return $this->uses()->escapeIdentifier($identifier, ...$args);
    }

    /**
     * Lists table indexes
     *
     * @param string $table
     * @param string $schema
     * @param array $args
     * @return IndexInterface[]
     */
    public function describeIndexes($table, $schema = null, ...$args)
    {
        return $this->uses()->describeIndexes($table, $schema, ...$args);
    }

    /**
     * Lists table references
     *
     * @param string $table
     * @param string $schema
     * @param array $args
     * @return ReferenceInterface[]
     */
    public function describeReferences($table, $schema = null, ...$args)
    {
        return $this->uses()->describeReferences($table, $schema, ...$args);
    }

    /**
     * Gets creation options from a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param array $args
     * @return array
     */
    public function tableOptions($tableName, $schemaName = null, ...$args)
    {
        return $this->uses()->tableOptions($tableName, $schemaName, ...$args);
    }

    /**
     * Check whether the database system requires an explicit value for identity columns
     *
     * @param array $args
     * @return bool
     */
    public function useExplicitIdValue(...$args)
    {
        return $this->uses()->useExplicitIdValue(...$args);
    }

    /**
     * Return the default identity value to insert in an identity column
     *
     * @param array $args
     * @return RawValue
     */
    public function getDefaultIdValue(...$args)
    {
        return $this->uses()->getDefaultIdValue(...$args);
    }

    /**
     * Check whether the database system requires a sequence to produce auto-numeric values
     *
     * @param array $args
     * @return bool
     */
    public function supportSequences(...$args)
    {
        return $this->uses()->supportSequences(...$args);
    }

    /**
     * Creates a new savepoint
     *
     * @param string $name
     * @param array $args
     * @return bool
     */
    public function createSavepoint($name, ...$args)
    {
        return $this->uses()->createSavepoint($name, ...$args);
    }

    /**
     * Releases given savepoint
     *
     * @param string $name
     * @param array $args
     * @return bool
     */
    public function releaseSavepoint($name, ...$args)
    {
        return $this->uses()->releaseSavepoint($name, ...$args);
    }

    /**
     * Rollbacks given savepoint
     *
     * @param string $name
     * @param array $args
     * @return bool
     */
    public function rollbackSavepoint($name, ...$args)
    {
        return $this->uses()->rollbackSavepoint($name, ...$args);
    }

    /**
     * Set if nested transactions should use savepoints
     *
     * @param bool $nestedTransactionsWithSavepoints
     * @param array $args
     * @return AdapterInterface
     */
    public function setNestedTransactionsWithSavepoints($nestedTransactionsWithSavepoints, ...$args)
    {
        return $this->uses()->setNestedTransactionsWithSavepoints($nestedTransactionsWithSavepoints, ...$args);
    }

    /**
     * Returns if nested transactions should use savepoints
     *
     * @param array $args
     * @return bool
     */
    public function isNestedTransactionsWithSavepoints(...$args)
    {
        return $this->uses()->isNestedTransactionsWithSavepoints(...$args);
    }

    /**
     * Returns the savepoint name to use for nested transactions
     *
     * @param array $args
     * @return string
     */
    public function getNestedTransactionSavepointName(...$args)
    {
        return $this->uses()->getNestedTransactionSavepointName(...$args);
    }
}

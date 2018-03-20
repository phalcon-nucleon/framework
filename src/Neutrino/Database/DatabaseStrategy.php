<?php

namespace Neutrino\Database;

use Neutrino\Constants\Services;
use Neutrino\Support\DesignPatterns\Strategy;
use Phalcon\Db\Adapter;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\ResultInterface;
use Phalcon\Di;

class DatabaseStrategy extends Adapter implements Strategy\StrategyInterface
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
        if(isset($this->$service)){
            return $this->$service;
        }

        if(!isset($this->_di)){
            $this->_di = Di::getDefault();
        }

        return $this->$service = $this->_di->getShared($service);
    }

    /**
     * This method is automatically called in \Phalcon\Db\Adapter\Pdo constructor.
     * Call it when you need to restore a database connection
     *
     * @param array $descriptor
     * @return bool
     */
    public function connect(array $descriptor = null)
    {
        return $this->uses()->connect($descriptor);
    }

    /**
     * Sends SQL statements to the database server returning the success state.
     * Use this method only when the SQL statement sent to the server return rows
     *
     * @param string $sqlStatement
     * @param mixed $placeholders
     * @param mixed $dataTypes
     * @return bool|ResultInterface
     */
    public function query($sqlStatement, $placeholders = null, $dataTypes = null)
    {
        return $this->uses()->query($sqlStatement, $placeholders, $dataTypes);
    }

    /**
     * Sends SQL statements to the database server returning the success state.
     * Use this method only when the SQL statement sent to the server doesn't return any rows
     *
     * @param string $sqlStatement
     * @param mixed $placeholders
     * @param mixed $dataTypes
     * @return bool
     */
    public function execute($sqlStatement, $placeholders = null, $dataTypes = null)
    {
        return $this->uses()->execute($sqlStatement, $placeholders, $dataTypes);
    }

    /**
     * Returns the number of affected rows by the last INSERT/UPDATE/DELETE reported by the database system
     *
     * @return int
     */
    public function affectedRows()
    {
        return $this->uses()->affectedRows();
    }

    /**
     * Closes active connection returning success. Phalcon automatically closes
     * and destroys active connections within Phalcon\Db\Pool
     *
     * @return bool
     */
    public function close()
    {
        return $this->uses()->close();
    }

    /**
     * Escapes a value to avoid SQL injections
     *
     * @param string $str
     * @return string
     */
    public function escapeString($str)
    {
        return $this->uses()->escapeString($str);
    }

    /**
     * Returns insert id for the auto_increment column inserted in the last SQL statement
     *
     * @param string $sequenceName
     * @return int
     */
    public function lastInsertId($sequenceName = null)
    {
        return $this->uses()->lastInsertId($sequenceName);
    }

    /**
     * Starts a transaction in the connection
     *
     * @param bool $nesting
     * @return bool
     */
    public function begin($nesting = true)
    {
        return $this->uses()->begin($nesting);
    }

    /**
     * Rollbacks the active transaction in the connection
     *
     * @param bool $nesting
     * @return bool
     */
    public function rollback($nesting = true)
    {
        return $this->uses()->rollback($nesting);
    }

    /**
     * Commits the active transaction in the connection
     *
     * @param bool $nesting
     * @return bool
     */
    public function commit($nesting = true)
    {
        return $this->uses()->commit($nesting);
    }

    /**
     * Checks whether connection is under database transaction
     *
     * @return bool
     */
    public function isUnderTransaction()
    {
        return $this->uses()->isUnderTransaction();
    }

    /**
     * Return internal PDO handler
     *
     * @return \Pdo
     */
    public function getInternalHandler()
    {
        return $this->uses()->getInternalHandler();
    }

    /**
     * Returns an array of Phalcon\Db\Column objects describing a table
     *
     * @param string $table
     * @param string $schema
     * @return ColumnInterface[]
     */
    public function describeColumns($table, $schema = null)
    {
        return $this->uses()->describeColumns($table, $schema);
    }

    public function __call($name, $arguments)
    {
        return $this->uses()->$name(...$arguments);
    }
}

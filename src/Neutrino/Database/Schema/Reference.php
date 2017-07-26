<?php

namespace Neutrino\Database\Schema;

/**
 * Class Reference
 *
 * @package Neutrino\Database\Schema
 */
class Reference extends \Phalcon\Db\Reference
{
    public function references($columns)
    {
        $this->_referencedColumns = is_array($columns) ? $columns : [$columns];

        return $this;
    }

    public function on($table)
    {
        $this->_referencedTable = $table;

        return $this;
    }

    public function onDelete($onDelete)
    {
        $this->_onDelete = $onDelete;

        return $this;
    }

    public function onUpdate($onUpdate)
    {
        $this->_onUpdate = $onUpdate;

        return $this;
    }
}
<?php

namespace Neutrino\Database\Migrations\Storage\Database;

use Neutrino\Model;
use Phalcon\Db\Column;

/**
 * Class MigrationModel
 *
 * @package Neutrino\Database\Migrations\Storage\Database
 */
class MigrationModel extends Model
{
    public $id;

    public $migration;

    public $batch;

    /**
     * Initializes metaDatas & columnsMap if they are not.
     */
    public function initialize()
    {
        parent::initialize();

        $this->setSource('migrations');

        $this->primary('id', Column::TYPE_INTEGER, [
            'unsigned'      => true,
            'autoIncrement' => true
        ]);

        $this->column('migration', Column::TYPE_VARCHAR);
        $this->column('batch', Column::TYPE_INTEGER);
    }
}

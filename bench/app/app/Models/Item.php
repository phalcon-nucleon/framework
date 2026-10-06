<?php

namespace Bench\Models;

use Neutrino\Model;
use Phalcon\Db\Column;

class Item extends Model
{
    public function initialize()
    {
        parent::initialize();

        $this->setSource('items');

        $this->primary('id', Column::TYPE_INTEGER);
        $this->column('name', Column::TYPE_VARCHAR);
        $this->column('price', Column::TYPE_DECIMAL);
        $this->column('stock', Column::TYPE_INTEGER, ['default' => 0]);
        $this->column('description', Column::TYPE_TEXT, ['nullable' => true]);
        $this->column('active', Column::TYPE_BOOLEAN);
        $this->timestamps();
    }
}

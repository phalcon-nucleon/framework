<?php

namespace Bench\Models;

use Neutrino\Model;
use Neutrino\Model\Attribute\Column;
use Neutrino\Model\Attribute\Primary;
use Neutrino\Model\Attribute\Timestamps;
use Phalcon\Db\Column as Type;

#[Timestamps]
class AttributeItem extends Model
{
    #[Primary]
    public ?int $id = null;

    #[Column(Type::TYPE_VARCHAR)]
    public ?string $name = null;

    #[Column(Type::TYPE_DECIMAL)]
    public mixed $price = null;

    #[Column(Type::TYPE_INTEGER, default: 0)]
    public mixed $stock = null;

    #[Column(Type::TYPE_TEXT, nullable: true)]
    public ?string $description = null;

    #[Column(Type::TYPE_BOOLEAN)]
    public mixed $active = null;

    public ?string $created_at = null;

    public ?string $updated_at = null;

    public function initialize()
    {
        parent::initialize();

        $this->setSource('items');
    }
}

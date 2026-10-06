<?php

declare(strict_types=1);

namespace Test\Models\Stub;

use Neutrino\Model;
use Neutrino\Model\Attribute\Column;
use Neutrino\Model\Attribute\Primary;
use Neutrino\Model\Attribute\SoftDelete;
use Neutrino\Model\Attribute\Timestamps;
use Phalcon\Db\Column as Type;

/**
 * Described with attributes.
 */
#[Timestamps]
#[SoftDelete('removed')]
class Author extends Model
{
    #[Primary(name: 'author_id')]
    public ?int $id = null;

    #[Column(Type::TYPE_VARCHAR)]
    public ?string $name = null;

    #[Column(Type::TYPE_VARCHAR, name: 'mail', nullable: true)]
    public ?string $email = null;

    #[Column(Type::TYPE_INTEGER, default: 1)]
    public ?int $level = null;

    public ?string $created_at = null;

    public ?string $updated_at = null;

    public mixed $removed = null;

    public function initialize()
    {
        parent::initialize();

        $this->setSource('authors');
    }
}

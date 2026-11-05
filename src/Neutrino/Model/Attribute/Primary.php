<?php

declare(strict_types=1);

namespace Neutrino\Model\Attribute;

use Attribute;
use Phalcon\Db\Column as DbColumn;

/**
 * The primary key column of a model property: `#[Primary] public ?int $id = null;`.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Primary
{
    /**
     * @param int|null    $type          A `Phalcon\Db\Column::TYPE_*` constant
     * @param string|null $name          Column in the table, when it differs from the property
     * @param bool        $identity      The column is the identity of the table
     * @param bool        $autoIncrement The database sets the value on insert
     */
    public function __construct(
        public ?int $type = DbColumn::TYPE_INTEGER,
        public ?string $name = null,
        public bool $identity = true,
        public bool $autoIncrement = true,
    ) {}
}

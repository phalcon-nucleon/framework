<?php

declare(strict_types=1);

namespace Neutrino\Model\Attribute;

use Attribute;

/**
 * A column of a model property: `#[Column(Column::TYPE_VARCHAR, nullable: true)] public ?string $name = null;`.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Column
{
    /**
     * @param int|null    $type       A `Phalcon\Db\Column::TYPE_*` constant
     * @param string|null $name       Column in the table, when it differs from the property
     * @param mixed       $default    Default value of the database
     * @param bool        $autoInsert The database sets the value on insert
     * @param bool        $autoUpdate The database sets the value on update
     */
    public function __construct(
        public ?int $type,
        public ?string $name = null,
        public bool $nullable = false,
        public mixed $default = null,
        public bool $autoInsert = false,
        public bool $autoUpdate = false,
    ) {}
}

<?php

declare(strict_types=1);

namespace Neutrino\Cli\Attribute;

use Attribute;

/**
 * Argument of a task action, shown by `help`: `#[Argument('name', 'Name of the migration.')]`.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Argument
{
    public function __construct(public readonly string $name, public readonly string $description = '') {}

    public function __toString(): string
    {
        return $this->description === '' ? $this->name : $this->name . ' : ' . $this->description;
    }
}

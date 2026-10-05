<?php

declare(strict_types=1);

namespace Neutrino\Cli\Attribute;

use Attribute;

/**
 * Option of a task action, shown by `help`: `#[Option('-f, --force', 'Force the operation.')]`.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Option
{
    public function __construct(public readonly string $name, public readonly string $description = '') {}

    public function __toString(): string
    {
        return $this->description === '' ? $this->name : $this->name . ' : ' . $this->description;
    }
}

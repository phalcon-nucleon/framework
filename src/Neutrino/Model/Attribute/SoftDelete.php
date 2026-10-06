<?php

declare(strict_types=1);

namespace Neutrino\Model\Attribute;

use Attribute;

/**
 * A soft delete column: `delete()` sets it instead of deleting the row. `#[SoftDelete] class User extends Model`.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class SoftDelete
{
    public function __construct(public string $column = 'deleted', public mixed $value = true) {}
}

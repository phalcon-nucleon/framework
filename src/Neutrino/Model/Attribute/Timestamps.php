<?php

declare(strict_types=1);

namespace Neutrino\Model\Attribute;

use Attribute;

/**
 * `created_at` and `updated_at` columns, set on create and on update: `#[Timestamps] class User extends Model`.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Timestamps
{
    public function __construct(
        public string $createdAt = 'created_at',
        public string $updatedAt = 'updated_at',
        public string $format = DATE_ATOM,
    ) {}
}

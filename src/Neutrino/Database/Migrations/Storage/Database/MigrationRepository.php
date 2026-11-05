<?php

declare(strict_types=1);

namespace Neutrino\Database\Migrations\Storage\Database;

use Neutrino\Repositories\Repository;

/**
 * Queries of the `migrations` table.
 */
class MigrationRepository extends Repository
{
    protected ?string $modelClass = MigrationModel::class;
}

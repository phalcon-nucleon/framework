<?php

namespace Neutrino\Database\Migrations\Storage\Database;

use Neutrino\Repositories\Repository;

/**
 * Class MigrationRepository
 *
 * @package     Neutrino\Database\Migrations\Storage\Database
 */
class MigrationRepository extends Repository
{
    protected $modelClass = MigrationModel::class;
}

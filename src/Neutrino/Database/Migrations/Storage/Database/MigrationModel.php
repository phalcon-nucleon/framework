<?php

declare(strict_types=1);

namespace Neutrino\Database\Migrations\Storage\Database;

use Neutrino\Constants\Services;
use Neutrino\Model;
use Neutrino\Model\Attribute\Column;
use Neutrino\Model\Attribute\Primary;
use Phalcon\Config\Config;
use Phalcon\Db\Column as Type;

/**
 * A row of the `migrations` table, on the `migrations.connection` connection (the default one otherwise).
 */
class MigrationModel extends Model
{
    #[Primary]
    public ?int $id = null;

    #[Column(Type::TYPE_VARCHAR)]
    public ?string $migration = null;

    #[Column(Type::TYPE_INTEGER)]
    public ?int $batch = null;

    public function initialize()
    {
        parent::initialize();

        $this->setSource('migrations');

        $config = $this->getDI()->has(Services::CONFIG) ? $this->getDI()->getShared(Services::CONFIG) : null;
        $connection = $config instanceof Config ? $config->path('migrations.connection') : null;

        if (is_string($connection) && $connection !== '') {
            $this->setConnectionService(Services::DB . '.' . $connection);
        }
    }
}

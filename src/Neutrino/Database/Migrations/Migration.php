<?php

declare(strict_types=1);

namespace Neutrino\Database\Migrations;

/**
 * Base class of the migrations.
 *
 * A migration file returns an instance (`return new class extends Migration { … };`), or declares a class named
 * after the file (`1511272551_create_users_table.php`: `class CreateUsersTable extends Migration`).
 */
abstract class Migration implements MigrationInterface
{
    /**
     * Connection of the migration (`db.<name>`): the default one, or the `--database` option, when `null`.
     */
    protected ?string $connection = null;

    /**
     * Runs the migration in a transaction, when the database can roll back its schema changes (PostgreSQL,
     * SQLite). Without effect on MySQL, which commits each DDL statement.
     */
    protected bool $withinTransaction = true;

    public function getConnection(): ?string
    {
        return $this->connection;
    }

    public function withinTransaction(): bool
    {
        return $this->withinTransaction;
    }
}

<?php

declare(strict_types=1);

namespace Neutrino\Database\Migrations;

use Neutrino\Database\Schema\Builder;

/**
 * A migration: `up()` applies a change to the schema, `down()` reverts it.
 *
 * The methods are not typed on their return: the 1.3 migrations declare them untyped.
 */
interface MigrationInterface
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(Builder $schema);

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(Builder $schema);
}

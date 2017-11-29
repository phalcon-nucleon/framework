<?php

namespace Neutrino\Database\Migrations;

use Neutrino\Database\Schema\Builder;

/**
 * Interface MigrationInterface
 *
 * @package Neutrino\Database\Schema
 */
interface MigrationInterface
{
    /**
     * Run the migrations.
     *
     * @param \Neutrino\Database\Schema\Builder $schema
     *
     * @return void
     */
    public function up(Builder $schema);

    /**
     * Reverse the migrations.
     *
     * @param \Neutrino\Database\Schema\Builder $schema
     *
     * @return void
     */
    public function down(Builder $schema);
}

<?php

use Neutrino\Database\Migrations\Migration;
use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Builder;

/**
 * A Nucleon 1.3 migration: a class named after the file, untyped.
 */
class CreateUsersTable extends Migration
{
    public function up(Builder $schema)
    {
        $schema->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
        });
    }

    public function down(Builder $schema)
    {
        $schema->dropIfExists('users');
    }
}

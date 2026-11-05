<?php

use Neutrino\Database\Migrations\Migration;
use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Builder;

/**
 * Rebuilds a referenced table, the SQLite way: needs the foreign keys disabled inside the transaction.
 */
return new class extends Migration {
    public function up(Builder $schema): void
    {
        $schema->withoutForeignKeyConstraints(function () use ($schema) {
            $schema->create('users_new', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100);
            });
            $schema->execute('INSERT INTO users_new (id, name) SELECT id, name FROM users');
            $schema->drop('users');
            $schema->rename('users_new', 'users');
        });
    }

    public function down(Builder $schema): void
    {
    }
};

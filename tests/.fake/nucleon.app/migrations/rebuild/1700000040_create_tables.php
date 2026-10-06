<?php

use Neutrino\Database\Migrations\Migration;
use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Builder;

return new class extends Migration {
    public function up(Builder $schema): void
    {
        $schema->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
        $schema->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->foreign()->references('id')->on('users');
        });
        $schema->execute("INSERT INTO users (id, name) VALUES (1, 'Ada')");
        $schema->execute('INSERT INTO posts (user_id) VALUES (1)');
    }

    public function down(Builder $schema): void
    {
        $schema->dropIfExists('posts');
        $schema->dropIfExists('users');
    }
};

<?php

use Neutrino\Database\Migrations\Migration;
use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Builder;

return new class extends Migration {
    public function up(Builder $schema): void
    {
        $schema->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->foreign()->references('id')->on('users');
            $table->string('title');
        });
    }

    public function down(Builder $schema): void
    {
        $schema->dropIfExists('posts');
    }
};

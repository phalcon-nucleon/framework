<?php

use Neutrino\Database\Migrations\Migration;
use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Builder;

return new class extends Migration {
    public function up(Builder $schema): void
    {
        $schema->create('logs', fn(Blueprint $table) => $table->increments('id'));
    }

    public function down(Builder $schema): void
    {
        $schema->dropIfExists('logs');
    }
};

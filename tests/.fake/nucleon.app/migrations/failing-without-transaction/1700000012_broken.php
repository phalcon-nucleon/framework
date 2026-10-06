<?php

use Neutrino\Database\Migrations\Migration;
use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Builder;

return new class extends Migration {
    protected bool $withinTransaction = false;

    public function up(Builder $schema): void
    {
        $schema->create('broken', fn(Blueprint $table) => $table->increments('id'));

        throw new RuntimeException('Broken migration.');
    }

    public function down(Builder $schema): void
    {
        $schema->dropIfExists('broken');
    }
};

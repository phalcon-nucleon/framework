<?php

use Neutrino\Database\Migrations\Migration;
use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Builder;

return new class extends Migration {
    protected ?string $connection = 'reports';

    public function up(Builder $schema): void
    {
        $schema->create('reports', fn(Blueprint $table) => $table->increments('id'));
    }

    public function down(Builder $schema): void
    {
        $schema->dropIfExists('reports');
    }
};

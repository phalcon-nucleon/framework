<?php

use Neutrino\Database\Migrations\Migration;
use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Builder;

return new class extends Migration {
    public function up(Builder $schema): void
    {
        $schema->table('posts', function (Blueprint $table) {
            $table->boolean('published')->default(false)->index();
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_published_index');
            $table->dropColumn('published');
        });
    }
};

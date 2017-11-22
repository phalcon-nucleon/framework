<?php

class CreateOne extends \Neutrino\Database\Migrations\Migration
{

    /**
     * Run the migrations.
     *
     * @param \Neutrino\Database\Schema\Builder $schema
     *
     * @return void
     */
    public function up(\Neutrino\Database\Schema\Builder $schema)
    {
        global $listeners;

        if (!isset($listeners[__METHOD__])) {
            $listeners[__METHOD__] = 0;
        }
        $listeners[__METHOD__]++;
    }

    /**
     * Reverse the migrations.
     *
     * @param \Neutrino\Database\Schema\Builder $schema
     *
     * @return void
     */
    public function down(\Neutrino\Database\Schema\Builder $schema)
    {
        global $listeners;

        if (!isset($listeners[__METHOD__])) {
            $listeners[__METHOD__] = 0;
        }
        $listeners[__METHOD__]++;
    }
}
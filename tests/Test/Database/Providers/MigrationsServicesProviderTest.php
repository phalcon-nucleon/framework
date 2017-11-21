<?php

namespace Test\Database\Providers;

use Neutrino\Database\Migrations\MigrationCreator;
use Neutrino\Database\Migrations\Migrator;
use Neutrino\Database\Migrations\Prefix\PrefixInterface;
use Neutrino\Database\Migrations\Storage\StorageInterface;
use Neutrino\Database\Providers\MigrationsServicesProvider;
use Test\TestCase\TestCase;

class MigrationsServicesProviderTest extends TestCase
{
    public function testRegistering()
    {
        $provider = new MigrationsServicesProvider();
        $provider->registering();

        $this->assertTrue($this->getDI()->has(PrefixInterface::class));
        $this->assertTrue($this->getDI()->has(StorageInterface::class));
        $this->assertTrue($this->getDI()->has(Migrator::class));
        $this->assertTrue($this->getDI()->has(MigrationCreator::class));
    }
}

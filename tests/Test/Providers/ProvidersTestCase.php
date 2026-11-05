<?php

declare(strict_types=1);

namespace Test\Providers;

use Neutrino\Config\Config;
use Neutrino\Constants\Services;
use Neutrino\Foundation\ProviderRegistrar;
use Phalcon\Di\Di;
use Phalcon\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

/**
 * Registers providers in a container of their own.
 */
abstract class ProvidersTestCase extends TestCase
{
    protected function tearDown(): void
    {
        Di::reset();
    }

    /**
     * @param list<class-string> $providers
     * @param array<string, mixed> $config
     */
    protected function container(array $providers, array $config = []): FactoryDefault
    {
        $di = new FactoryDefault();
        $di->setShared(Services::CONFIG, new Config($config));

        ProviderRegistrar::register($di, $providers);

        return $di;
    }
}

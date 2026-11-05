<?php

declare(strict_types=1);

namespace Neutrino\Foundation;

use Neutrino\Interfaces\Providable;
use Phalcon\Di\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Di\Service;
use UnexpectedValueException;

/**
 * Registers the `$providers` list of a kernel or a module.
 *
 * Each entry is either:
 * - `'name' => Class::class`: a shared service, registered under both the name and the class;
 * - `Provider::class`: a {@see Providable}, which registers its own definitions.
 *
 * @internal
 */
final class ProviderRegistrar
{
    /**
     * @param array<int|string, string> $providers
     */
    public static function register(DiInterface $di, array $providers): void
    {
        foreach ($providers as $name => $provider) {
            if (is_string($name)) {
                $service = new Service($provider, true);

                $di->setService($name, $service);
                $di->setService($provider, $service);

                continue;
            }

            $instance = new $provider();

            if (!$instance instanceof Providable) {
                throw new UnexpectedValueException(sprintf('Provider "%s" must implement %s.', $provider, Providable::class));
            }

            if ($instance instanceof InjectionAwareInterface) {
                $instance->setDI($di);
            }

            $instance->registering();
        }
    }
}

<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Interfaces\Providable;
use Phalcon\Config\Config;
use Phalcon\Di\Injectable;
use Phalcon\Di\Service;
use Phalcon\Session\Adapter\Libmemcached;
use Phalcon\Session\Adapter\Noop;
use Phalcon\Session\Adapter\Redis;
use Phalcon\Session\Adapter\Stream;
use Phalcon\Session\Bag;
use Phalcon\Session\Manager;
use Phalcon\Storage\AdapterFactory;
use Phalcon\Storage\SerializerFactory;
use RuntimeException;
use SessionHandlerInterface;
use Throwable;

/**
 * The `session` service: a {@see Manager} on the store `session.default` of `session.stores`, started on its
 * first use. And the `sessionBag` factory: `$di->get(Services::SESSION_BAG, ['name'])`.
 *
 * A store is `['adapter' => 'stream', 'options' => [...], 'name' => 'PHPSESSID']`:
 * - `adapter`: `stream` (files, option `savePath`), `redis`, `libmemcached`, `noop`, or a class implementing
 *   {@see SessionHandlerInterface}, built with `new $class($options)`;
 * - `options`: the options of the adapter, and `uniqueId` (prefix of the keys of the manager);
 * - `name`: optional session name.
 *
 * A single store can also be set directly in `session` (`session.adapter`, `session.options`).
 */
class Session extends Injectable implements Providable
{
    public function registering(): void
    {
        $di = $this->getDI();
        $self = $this;

        $di->setService(Services::SESSION_BAG, new Service(function (string $name) use ($di): Bag {
            /** @var Manager $session */
            $session = $di->getShared(Services::SESSION);

            return new Bag($session, $name);
        }, false));

        // Phalcon binds closure definitions to the container: $this would be the Di, not the provider.
        $session = new Service(function () use ($self): Manager {
            return $self->makeSession();
        }, true);

        $di->setService(Services::SESSION, $session);
        $di->setService(Manager::class, $session);
    }

    /**
     * Builds and starts the session.
     */
    public function makeSession(): Manager
    {
        /** @var Config $config */
        $config = $this->getDI()->getShared(Services::CONFIG);
        $session = $config->path('session');
        $session = $session instanceof Config ? $session->toArray() : [];

        if (!isset($session['adapter'])) {
            $default = is_string($session['default'] ?? null) ? $session['default'] : '';
            $stores = is_array($session['stores'] ?? null) ? $session['stores'] : [];

            if (!isset($stores[$default]) || !is_array($stores[$default])) {
                throw new RuntimeException("Session store \"$default\" not found in stores.");
            }

            $session = $stores[$default];
        }

        $options = $session['options'] ?? [];
        if (!is_array($options)) {
            throw new RuntimeException('Session: "options" must be an array.');
        }

        $manager = new Manager(['uniqueId' => is_string($options['uniqueId'] ?? null) ? $options['uniqueId'] : '']);
        $manager->setAdapter($this->makeAdapter($session['adapter'] ?? null, $options));

        if (is_string($session['name'] ?? null) && $session['name'] !== '') {
            $manager->setName($session['name']);
        }

        $manager->start();

        return $manager;
    }

    /**
     * @param array<mixed> $options
     */
    private function makeAdapter(mixed $adapter, array $options): SessionHandlerInterface
    {
        if (!is_string($adapter) || $adapter === '') {
            throw new RuntimeException('Session: no adapter.');
        }

        // Nucleon 1.3 accepted the class names; the Phalcon 5 Redis and Libmemcached adapters need a factory.
        $known = match ($adapter) {
            Redis::class        => 'redis',
            Libmemcached::class => 'libmemcached',
            default             => strtolower($adapter),
        };

        if ($known === 'files') {
            throw new RuntimeException('Session adapter "Files" was removed with Phalcon 5: use "stream".');
        }

        if (!in_array($known, ['stream', 'noop', 'redis', 'libmemcached'], true)
            && (!class_exists($adapter) || !is_subclass_of($adapter, SessionHandlerInterface::class))
        ) {
            throw new RuntimeException("Session adapter \"$adapter\" not found: use stream, redis, libmemcached, noop or a class implementing " . SessionHandlerInterface::class . '.');
        }

        try {
            /** @var SessionHandlerInterface */
            return match ($known) {
                'stream'       => new Stream($options), // @phpstan-ignore argument.type (options of the config, checked by Phalcon)
                'noop'         => new Noop(),
                'redis'        => new Redis(new AdapterFactory(new SerializerFactory()), $options), // @phpstan-ignore argument.type (options of the config, checked by Phalcon)
                'libmemcached' => new Libmemcached(new AdapterFactory(new SerializerFactory()), $options), // @phpstan-ignore argument.type (options of the config, checked by Phalcon)
                default        => new $adapter($options),
            };
        } catch (Throwable $e) {
            throw new RuntimeException("Session adapter \"$adapter\" construction failed: " . $e->getMessage(), 0, $e);
        }
    }
}

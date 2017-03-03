<?php

namespace Neutrino\Providers;

use Neutrino\Interfaces\Providable;
use Phalcon\Di\Injectable;
use Phalcon\Di\Service;

/**
 * Class Provider
 *
 * @package Neutrino\Providers
 */
abstract class Provider extends Injectable implements Providable
{
    /**
     * Name of the service
     *
     * @var string
     */
    protected $name;

    /**
     * @var string[]
     */
    protected $aliases;

    /**
     * @var bool
     */
    protected $shared = false;

    /**
     * Provider constructor.
     */
    final public function __construct()
    {
        if (empty($this->name)) {
            throw new \RuntimeException('Provider "' . static::class . '" have no name.');
        }
    }

    /**
     * @inheritdoc
     */
    public function registering()
    {
        $self = $this;

        $service = new Service($this->name, function () use ($self) {
            return $self->register();
        }, $this->shared);

        $this->getDI()->setRaw($this->name, $service);

        if (!empty($this->aliases)) {
            foreach ($this->aliases as $alias) {
                $this->getDI()->setRaw($alias, $service);
            }
        }
    }

    /**
     * Return the service to register
     *
     * Called when the services container tries to resolve the service
     *
     * @return mixed
     */
    abstract protected function register();
}

<?php

namespace Neutrino\Providers;

use Neutrino\Interfaces\Providable;
use Phalcon\Di\Injectable;

/**
 * Class Provider
 *
 *  @package Neutrino\Providers
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
     * @var bool
     */
    protected $shared = false;

    /**
     * Provider constructor.
     */
    final public function __construct()
    {
        if (empty($this->name)) {
            throw new \RuntimeException('Provider ' . static::class . ' have no name.');
        }
    }

    /**
     * @inheritdoc
     */
    public function registering()
    {
        $self = $this;

        $this->getDI()->set($this->name, function () use ($self) {
            return $self->register();
        }, $this->shared);
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

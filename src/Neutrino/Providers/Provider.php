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
     * @var mixed
     */
    private $instance;

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
     * @var string[]
     */
    protected $aliases;

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

        $closure = function () use ($self) {
            if ($self->shared && isset($self->instance)) {
                return $self->instance;
            }

            return $self->instance = $self->register();
        };

        $this->getDI()->set($this->name, $closure, $this->shared);

        if(!empty($this->aliases)){
            foreach ($this->aliases as $alias) {
                $this->getDI()->set($alias, $closure, $this->shared);
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

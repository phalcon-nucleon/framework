<?php

namespace Luxury\Providers;

use Luxury\Interfaces\Providable;
use Luxury\Support\Traits\InjectionAwareTrait;
use Phalcon\Di\InjectionAwareInterface;

/**
 * Class Provider
 *
 * @package     Luxury\Providers
 */
abstract class Provider implements Providable, InjectionAwareInterface
{
    use InjectionAwareTrait;

    /**
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
     *
     */
    public function registering()
    {
        $self = $this;

        $this->getDI()->set($this->name, function () use ($self) {
            return $self->register();
        }, $this->shared);
    }

    /**
     * @return mixed
     */
    abstract protected function register();
}

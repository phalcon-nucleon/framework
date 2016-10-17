<?php

namespace Luxury\Providers;

use Luxury\Interfaces\Providable;
use Luxury\Support\Traits\InjectionAwareTrait;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\DiInterface;

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
     * @param \Phalcon\DiInterface $di
     */
    public function registering(DiInterface $di)
    {
        $self = $this;

        $this->getDI()->set($this->name, function () use ($self, $di) {
            return $self->register($di);
        }, $this->shared);
    }

    /**
     * @param \Phalcon\DiInterface $di
     *
     * @return mixed
     */
    abstract protected function register(DiInterface $di);
}

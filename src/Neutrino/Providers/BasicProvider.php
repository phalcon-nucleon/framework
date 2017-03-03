<?php

namespace Neutrino\Providers;

use Neutrino\Interfaces\Providable;
use Phalcon\Di\Injectable;
use Phalcon\Di\Service;

abstract class BasicProvider extends Injectable implements Providable
{
    /**
     * Class to provide
     *
     * @var string
     */
    protected $class;

    /**
     * Name of the service
     *
     * @var string
     */
    protected $name;

    /**
     * Aliases
     *
     * @var string[]
     */
    protected $aliases;

    /**
     * Shared Service
     *
     * @var bool
     */
    protected $shared = false;

    /**
     * Options to pass to the definition
     *
     * @var array
     */
    protected $options;

    public function registering()
    {
        if (!empty($this->options)) {
            $definition = array_merge(['className' => $this->class], $this->options);
        } else {
            $definition = $this->class;
        }

        $service = new Service($this->name, $definition, $this->shared);

        $this->getDI()->setRaw($this->name, $service);

        foreach ($this->aliases as $alias) {
            $this->getDI()->setRaw($alias, $service);
        }
    }
}

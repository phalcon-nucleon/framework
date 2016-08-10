<?php

namespace Luxury\Foundation\Application;

use Luxury\Foundation\Kernelize;
use Luxury\Interfaces\Kernelable;
use Phalcon\Cli\Console;
use Phalcon\Di\FactoryDefault\Cli as Di;

/**
 * Class Cli
 *
 * @package Luxury\Foundation\Application
 */
abstract class Cli extends Console implements Kernelable
{
    use Kernelize;

    /**
     * Return the Provider List to load.
     *
     * @var string[]
     */
    protected $providers = [];

    /**
     * Return the Middleware List to load.
     *
     * @var string[]
     */
    protected $middlewares = [];

    /**
     * The DependencyInjection class to use.
     *
     * @var string
     */
    protected $dependencyInjection = Di::class;

    /**
     * Application constructor.
     */
    public function __construct()
    {
        parent::__construct(null);
    }
}

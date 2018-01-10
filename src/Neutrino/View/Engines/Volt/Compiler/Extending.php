<?php

namespace Neutrino\View\Engines\Volt\Compiler;

use Phalcon\Mvc\View\Engine\Volt\Compiler;

/**
 * Class Extending
 *
 * @package Neutrino\View\Engine\Extending
 */
abstract class Extending
{
    /**
     * @var \Phalcon\Mvc\View\Engine\Volt\Compiler
     */
    protected $compiler;

    /**
     * Extending constructor.
     *
     * @param \Phalcon\Mvc\View\Engine\Volt\Compiler $compiler
     */
    public function __construct(Compiler $compiler)
    {
        $this->compiler = $compiler;
    }
}

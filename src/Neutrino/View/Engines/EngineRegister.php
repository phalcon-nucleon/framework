<?php

declare(strict_types=1);

namespace Neutrino\View\Engines;

use Closure;
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\View\Engine\EngineInterface;
use Phalcon\Mvc\ViewBaseInterface;

/**
 * Registers a template engine on the view, built on the first render that needs it.
 */
abstract class EngineRegister
{
    /**
     * The definition given to `View::registerEngines()`. Not static: the view binds it.
     */
    final public static function getRegisterClosure(): Closure
    {
        // The view binds the closure to the container: `static` would then be the container class.
        $register = static::class;

        return function (ViewBaseInterface $view, ?DiInterface $di = null) use ($register): EngineInterface {
            // Phalcon 5 passes the view only.
            $di ??= $view instanceof InjectionAwareInterface ? $view->getDI() : Di::getDefault();

            return (new $register())->register($view, $di ?? throw new \RuntimeException('No container for the view engine.'));
        };
    }

    /**
     * Not typed on the return: the 1.3 registers return their engine untyped.
     *
     * @return EngineInterface
     */
    abstract public function register(ViewBaseInterface $view, DiInterface $di);
}

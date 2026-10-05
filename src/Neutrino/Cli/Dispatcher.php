<?php

declare(strict_types=1);

namespace Neutrino\Cli;

/**
 * Console dispatcher: task actions receive the route parameters only.
 *
 * Phalcon 5 appends the options to the action arguments, as named arguments: an action without parameters
 * (`mainAction()`) then fails on any option ("Unknown named parameter"). Tasks read their options with
 * `getOption()` / `hasOption()`, as in 1.x.
 */
class Dispatcher extends \Phalcon\Cli\Dispatcher
{
    /**
     * @param array<int|string, mixed> $params
     */
    public function callActionMethod($handler, string $actionMethod, array $params = []): mixed
    {
        return $handler->{$actionMethod}(...array_values($params));
    }
}

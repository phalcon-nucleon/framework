<?php

declare(strict_types=1);

namespace Neutrino\Support\Facades;

use Neutrino\Constants\Services;

/**
 * The `view` service ({@see \Phalcon\Mvc\View}).
 *
 * @method static \Phalcon\Mvc\View setVar(string $key, mixed $value)
 * @method static \Phalcon\Mvc\View setVars(array<string, mixed> $params, bool $merge = true)
 * @method static \Phalcon\Mvc\View pick(string|array<string> $renderView)
 * @method static \Phalcon\Mvc\View disable()
 * @method static \Phalcon\Mvc\View setTemplateAfter(string|array<string> $templateAfter)
 * @method static string getPartial(string $partialPath, mixed $params = null)
 * @method static string getRender(string $controllerName, string $actionName, array<string, mixed> $params = [], mixed $configCallback = null)
 */
class View extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Services::VIEW;
    }
}

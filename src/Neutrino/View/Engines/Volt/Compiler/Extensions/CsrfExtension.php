<?php

declare(strict_types=1);

namespace Neutrino\View\Engines\Volt\Compiler\Extensions;

use Neutrino\Http\Middleware\Csrf;
use Neutrino\View\Engines\Volt\Compiler\ExtensionExtend;

/**
 * `{{ csrf_field() }}`: `<input type="hidden" name="_csrf_token" value="…">`, and `{{ csrf_token() }}`: the CSRF
 * token of the session ({@see Csrf::token()}), checked by the {@see Csrf} middleware.
 */
class CsrfExtension extends ExtensionExtend
{
    public function compileFunction(string $name, string $arguments, ?array $funcArguments): ?string
    {
        $token = '\\' . Csrf::class . '::token($this->getDI())';

        return match ($name) {
            'csrf_field' => "'<input type=\"hidden\" name=\"" . Csrf::FIELD . "\" value=\"' . \$this->escaper->attributes($token) . '\">'",
            'csrf_token' => $token,
            default      => null,
        };
    }
}

<?php

declare(strict_types=1);

namespace Neutrino\PHPStan;

use Neutrino\Support\DesignPatterns\Strategy;
use Neutrino\Support\DesignPatterns\Strategy\MagicCallStrategyTrait;
use Neutrino\Support\Traits\InjectionAwareTrait;
use Neutrino\Support\Traits\Macroable;

// PHPStan analyses a trait only in the context of a class that uses it:
// these classes make it analyse the traits that the framework provides to applications.

final class InjectionAwareUsage
{
    use InjectionAwareTrait;
}

final class MacroableUsage
{
    use Macroable;
}

final class MagicCallStrategyUsage extends Strategy
{
    use MagicCallStrategyTrait;
}

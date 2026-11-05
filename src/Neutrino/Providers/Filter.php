<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;
use Phalcon\Filter\Filter as PhalconFilter;
use Phalcon\Filter\FilterFactory;

/**
 * The `filter` service, with the sanitizers of Phalcon.
 */
class Filter extends Provider
{
    protected string $name = Services::FILTER;

    protected bool $shared = true;

    protected array $aliases = [PhalconFilter::class];

    protected function register(): PhalconFilter
    {
        /** @var PhalconFilter */
        return (new FilterFactory())->newInstance();
    }
}

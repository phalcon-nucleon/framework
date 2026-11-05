<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;
use Phalcon\Config\Config;
use Phalcon\Mvc\Url as MvcUrl;

/**
 * URL generator: `app.base_uri`, and `app.static_base_uri` for static resources (defaults to `base_uri`).
 */
class Url extends Provider
{
    protected string $name = Services::URL;

    protected bool $shared = true;

    protected array $aliases = [MvcUrl::class];

    protected function register(): MvcUrl
    {
        /** @var Config $config */
        $config = $this->getDI()->getShared(Services::CONFIG);

        $baseUri = $config->path('app.base_uri', '/');
        $baseUri = is_string($baseUri) ? $baseUri : '/';
        $staticBaseUri = $config->path('app.static_base_uri', $baseUri);

        $url = new MvcUrl();
        $url->setBaseUri($baseUri);
        $url->setStaticBaseUri(is_string($staticBaseUri) ? $staticBaseUri : $baseUri);

        return $url;
    }
}

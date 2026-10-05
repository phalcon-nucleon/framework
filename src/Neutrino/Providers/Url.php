<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;

/**
 * Class Url
 *
 *  @package Neutrino\Foundation\Bootstrap
 */
class Url extends Provider
{
    protected string $name = Services::URL;

    protected bool $shared = true;

    protected array $aliases = [\Phalcon\Mvc\Url::class];

    /**
     * The URL component is used to generate all kind of urls in the application
     *
     * @return \Phalcon\Mvc\Url
     */
    protected function register()
    {
        $url = new \Phalcon\Mvc\Url();

        $appConf = $this->getDI()->getShared(Services::CONFIG)->app;

        $url->setBaseUri($appConf->base_uri);
        $url->setStaticBaseUri(isset($appConf->static_base_uri) ? $appConf->static_base_uri : $appConf->base_uri);

        return $url;
    }
}

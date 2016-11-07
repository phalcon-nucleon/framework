<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Luxury\Support\Traits\InjectionAwareTrait;
use Luxury\View\Engine\Extensions\PhpFunction as PhpFunctionExtension;
use Phalcon\Assets\Manager as AssetsManager;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\DiInterface;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Tag;

/**
 * Class View
 *
 * @package Luxury\Foundation\Bootstrap
 */
class View implements Providable, InjectionAwareInterface
{
    use InjectionAwareTrait;

    /**
     * @return \Phalcon\Mvc\View
     */
    public function registering()
    {
        $di = $this->getDI();

        $di->setShared(Services::TAG, Tag::class);
        $di->setShared(Services::ASSETS, AssetsManager::class);

        $di->setShared(Services::VIEW, function () {
            /** @var DiInterface $this */

            $view = new \Phalcon\Mvc\View();

            $view->setViewsDir($this->getShared(Services::CONFIG)->view->views_dir);

            $view->registerEngines([
                '.volt'  => function ($view, $di) {
                    /* @var \Phalcon\Di $di */
                    $volt = new VoltEngine($view, $di);

                    $volt->setOptions([
                        'compiledPath'      => $di->getShared(Services::CONFIG)->view->compiled_path,
                        'compiledSeparator' => '_'
                    ]);
                    $volt->getCompiler()->addExtension(new PhpFunctionExtension());

                    return $volt;
                },
                '.phtml' => 'Phalcon\Mvc\View\Engine\Php'
            ]);

            return $view;
        });
    }
}

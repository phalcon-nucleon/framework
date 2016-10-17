<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\View\Engine\Extensions\PhpFunction as PhpFunctionExtension;
use Phalcon\DiInterface;

/**
 * Class View
 *
 * @package Luxury\Foundation\Bootstrap
 */
class View extends Provider
{
    protected $name = Services::VIEW;

    protected $shared = true;

    /**
     * @return \Phalcon\Mvc\View
     */
    public function registering()
    {
        $di = $this->getDI();

        $di->setShared(Services::TAG, \Phalcon\Tag::class);
        $di->setShared(Services::ASSETS, \Phalcon\Assets\Manager::class);

        $di->setShared($this->name, function () {
            /** @var DiInterface $this */

            $view = new \Phalcon\Mvc\View();

            $view->setViewsDir($this->getShared(Services::CONFIG)->view->viewsDir);

            $view->registerEngines([
                '.volt'  => function ($view, $di) {
                    /* @var \Phalcon\Di $di */
                    $volt = new \Phalcon\Mvc\View\Engine\Volt($view, $di);

                    $volt->setOptions([
                        'compiledPath'      => $di->getShared(Services::CONFIG)->view->compiledPath,
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

    /**
     * @return mixed
     */
    protected function register()
    {
        return;
    }
}

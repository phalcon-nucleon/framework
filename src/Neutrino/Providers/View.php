<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Env;
use Neutrino\Constants\Services;
use Neutrino\Interfaces\Providable;
use Phalcon\Assets\Manager as AssetsManager;
use Phalcon\Di\Injectable;
use Phalcon\DiInterface;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Tag;

/**
 * Class View
 *
 *  @package Neutrino\Foundation\Bootstrap
 */
class View extends Injectable implements Providable
{
    /**
     * @inheritdoc
     */
    public function registering()
    {
        $di = $this->getDI();

        $di->setShared(Services::TAG, Tag::class);
        $di->setShared(Tag::class, Tag::class);
        $di->setShared(Services::ASSETS, AssetsManager::class);
        $di->setShared(AssetsManager::class, AssetsManager::class);

        $di->setShared(Services::VIEW, function () {
            /** @var DiInterface $this */

            $view = new \Phalcon\Mvc\View();

            $configView = $this->getShared(Services::CONFIG)->view;
            if (isset($configView->views_dir)) {
                $view->setViewsDir($configView->views_dir);
            }
            if (isset($configView->partials_dir)) {
                $view->setPartialsDir($configView->partials_dir);
            }
            if (isset($configView->layouts_dir)) {
                $view->setLayoutsDir($configView->layouts_dir);
            }

            $view->registerEngines([
                '.volt'  => function ($view, $di) {
                    /* @var \Phalcon\Di $di */
                    $volt = new VoltEngine($view, $di);

                    $config = $di->getShared(Services::CONFIG)->view;

                    $options = array_merge(
                        [
                            'compiledPath' => $config->compiled_path,
                            'compiledSeparator' => '_',
                            'compileAlways' => APP_ENV === Env::DEVELOPMENT,
                        ],
                        isset($config->options) ? (array)$config->options : []
                    );

                    $volt->setOptions($options);

                    $compiler = $volt->getCompiler();

                    $extensions = isset($config->extensions) ? $config->extensions : [];
                    foreach ($extensions as $extension) {
                        $compiler->addExtension(new $extension($compiler));
                    }

                    $filters = isset($config->filters) ? $config->filters : [];
                    foreach ($filters as $name => $filter) {
                        $filter = new $filter($compiler);
                        $compiler->addFilter($name, function ($resolvedArgs, $exprArgs) use ($filter) {
                            /* @var \Neutrino\View\Engine\Compiler\FilterExtend $filter */
                            return $filter->compileFilter($resolvedArgs, $exprArgs);
                        });
                    }

                    $functions = isset($config->functions) ? $config->functions : [];
                    foreach ($functions as $name => $function) {
                        $function = new $function($compiler);
                        $compiler->addFunction($name, function ($resolvedArgs, $exprArgs) use ($function) {
                            /* @var \Neutrino\View\Engine\Compiler\FunctionExtend $function */
                            return $function->compileFunction($resolvedArgs, $exprArgs);
                        });
                    }

                    return $volt;
                },
                '.phtml' => 'Phalcon\Mvc\View\Engine\Php'
            ]);

            return $view;
        });
    }
}

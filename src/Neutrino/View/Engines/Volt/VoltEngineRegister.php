<?php

namespace Neutrino\View\Engines\Volt;

use Neutrino\Constants\Env;
use Neutrino\Constants\Services;
use Neutrino\View\Engines\EngineRegister;
use Phalcon\Mvc\View\Engine\Volt;

/**
 * Class VoltEngineRegister
 *
 * @package Neutrino\View\Engines\Volt
 */
class VoltEngineRegister extends EngineRegister
{

    /**
     * @param $view
     * @param $di
     *
     * @return \Phalcon\Mvc\View\Engine
     */
    public function register($view, $di)
    {
        /* @var \Phalcon\Di $di */
        $volt = new Volt($view, $di);

        $config = $di->getShared(Services::CONFIG)->view;

        $options = array_merge(
            [
                'compiledPath' => $config->compiled_path,
                'compiledSeparator' => '_',
                'compileAlways' => APP_ENV === Env::DEVELOPMENT || APP_DEBUG,
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
                /* @var \Neutrino\View\Engines\Volt\Compiler\FilterExtend $filter */
                return $filter->compileFilter($resolvedArgs, $exprArgs);
            });
        }

        $compiler->addFunction('dump', function ($resolvedArgs) {
            return 'Neutrino\Debug\Dumper::dump(' . $resolvedArgs . ')';
        });
        $functions = isset($config->functions) ? $config->functions : [];
        foreach ($functions as $name => $function) {
            $function = new $function($compiler);
            $compiler->addFunction($name, function ($resolvedArgs, $exprArgs) use ($function) {
                /* @var \Neutrino\View\Engines\Volt\Compiler\FunctionExtend $function */
                return $function->compileFunction($resolvedArgs, $exprArgs);
            });
        }

        return $volt;
    }
}

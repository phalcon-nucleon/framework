<?php

namespace Neutrino\Assets\Closure;

/**
 * Class DebugSpeedhack
 *
 * @package Neutrino\Assets\Closure
 */
class DebugPrecompilation extends Precompilation
{

    public function precompile($content)
    {
        if ($this->options['debug']) {
            $debug = <<<JS
/**
* @param {...*} _arg
*/
function debug(_arg){
    console.log.apply(console, arguments);
}
JS;
        } else {
            $debug = <<<JS
/**
* @param {...*} _arg
*/
function debug(_arg){}
JS;
        }

        return $debug . $content;
    }
}

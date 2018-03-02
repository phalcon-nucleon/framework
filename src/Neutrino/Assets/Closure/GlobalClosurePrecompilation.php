<?php

namespace Neutrino\Assets\Closure;

/**
 * Class GlobalClosureSpeedhack
 *
 * @package Neutrino\Assets\Closure
 */
class GlobalClosurePrecompilation extends Precompilation
{

    public function precompile($content)
    {
        $closures = $this->options;

        $global = implode(',', array_keys($closures));
        $variables = implode(',', array_values($closures));

        return <<<JS
(function($variables){
  $content
})($global);
JS;
    }
}

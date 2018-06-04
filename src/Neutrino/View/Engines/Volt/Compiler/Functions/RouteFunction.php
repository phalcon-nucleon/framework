<?php

namespace Neutrino\View\Engines\Volt\Compiler\Functions;

use Neutrino\View\Engines\Volt\Compiler\FunctionExtend;

/**
 * Class RouteFunction
 *
 * @package     Neutrino\View\Engines\Volt\Compiler
 */
class RouteFunction extends FunctionExtend
{

    /**
     * @param string $resolvedArgs
     * @param array  $exprArgs
     *
     * @return string|null
     */
    public function compileFunction($resolvedArgs, $exprArgs)
    {
        if (isset($exprArgs[1]['expr']['left'])) {
            $route = $this->compiler->expression([
                'type' => 360, // type array
                'left' => array_merge([
                    [
                        'name' => 'for',
                        'expr' => $exprArgs[0]['expr']
                    ]
                ], $exprArgs[1]['expr']['left'])
            ]);
        } else {
            $route = "['for' => " . $this->compiler->expression($exprArgs[0]['expr']) . "]";
        }

        $url = "\$this->url->get($route";
        if (isset($exprArgs[2])) {
            $url .= ', ' . $this->compiler->expression($exprArgs[2]['expr']);
        }
        $url .= ')';

        return "str_replace(['/#^', '$#u'], '', $url)";
    }
}

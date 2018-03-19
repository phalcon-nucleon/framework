<?php

namespace Neutrino\View\Engines\Volt\Compiler\Filters;

use Neutrino\View\Engines\Volt\Compiler\FilterExtend;

/**
 * Class RoundFilter
 *
 * @package App\Core\View\Engines\Volt\Filters
 */
class RoundFilter extends FilterExtend
{
    /*
    #define PHVOLT_T_INTEGER 258
    #define PHVOLT_T_DOUBLE 259
    #define PHVOLT_T_STRING 260
    #define PHVOLT_T_NULL 261
    #define PHVOLT_T_FALSE 262
    #define PHVOLT_T_TRUE 263
     *
     */
    /**
     * @param string $resolvedArgs
     * @param array $exprArgs
     *
     * @return string|null
     */
    public function compileFilter($resolvedArgs, $exprArgs)
    {
        $value = isset($exprArgs[0]['expr']['value']) ? $exprArgs[0]['expr']['value'] : $resolvedArgs;

        switch (isset($exprArgs[1]['expr']['type']) ? $exprArgs[1]['expr']['type'] : null) {
            case 260:
                switch (isset($exprArgs[1]['expr']['value']) ? $exprArgs[1]['expr']['value'] : null) {
                    case 'floor':
                        return "floor($value)";
                    case 'ceil':
                        return "ceil($value)";
                }
        }

        $precision = isset($exprArgs[1]['expr']['value']) ? $exprArgs[1]['expr']['value'] : 0;

        switch (isset($exprArgs[2]['expr']['value']) ? $exprArgs[2]['expr']['value'] :  null) {
            case 'floor':
                return "floor($value*(10**$precision))/(10**$precision)";
            case 'ceil':
                return "ceil($value*(10**$precision))/(10**$precision)";
        }

        return "round($value, $precision)";
    }
}

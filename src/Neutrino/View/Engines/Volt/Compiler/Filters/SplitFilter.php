<?php

namespace Neutrino\View\Engines\Volt\Compiler\Filters;

use Neutrino\View\Engines\Volt\Compiler\FilterExtend;

/**
 * Class SplitFilter
 *
 * @package Neutrino\View\Engines\Volt\Compiler\Filters
 */
class SplitFilter extends FilterExtend
{

    /**
     * @param string $resolvedArgs
     * @param array  $exprArgs
     *
     * @return string|null
     */
    public function compileFilter($resolvedArgs, $exprArgs)
    {
        $value = isset($exprArgs[0]['expr']['value']) ? $exprArgs[0]['expr']['value'] : $resolvedArgs;

        $separator = isset($exprArgs[1]['expr']['value'])
            ? $exprArgs[1]['expr']['value']
            : '';

        if (empty($separator)) {

            $length = isset($exprArgs[2]['expr']['value'])
                ? $exprArgs[2]['expr']['value']
                : '1';

            return 'str_split(' . $value . ', ' . $length . ')';
        }

        if(isset($exprArgs[2]['expr']['value'])){
            return 'explode(' . var_export($separator, true) . ', ' . $value . ', ' . var_export($exprArgs[2]['expr']['value'], true) . ')';
        }

        return 'explode(' . var_export($separator, true) . ', ' . $value . ')';
    }
}

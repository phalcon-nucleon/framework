<?php

namespace Neutrino\Assets\Closure;

/**
 * Class JqueryIdHack
 *
 * @package Neutrino\Assets\Closure
 */
class JqueryIdPrecompilation extends Precompilation
{

    /**
     * Speed up jQuery ID Selector
     * Add function :
     * function jQuerySelectorSpeedhack(id) {
     *     return jQuery(document.getElementById(id))
     * }
     *
     * Replace :
     *  jQuery('#someid')   > jQuerySelectorSpeedhack('someid')
     *  $('#someid')        > jQuerySelectorSpeedhack('someid')
     *
     *  $('#someid .test')                      > jQuerySelectorSpeedhack('someid').find('.test')
     *  $('#someid li[data="test"]')            > jQuerySelectorSpeedhack('someid').find('li[data="test"]')
     *  $('#someid .item > span:not(.selected)')  > jQuerySelectorSpeedhack('someid').find('.item span:not(.selected)')
     *
     * @param $content
     *
     * @return string
     */
    public function precompile($content)
    {
        $selector = <<<JS
/**
* @param {string} id
* @return {(*|Window|Document|Element|Array<Element>|string|NodeList)}
*/
function jQuerySelectorSpeedhack(id) {
    return jQuery(document.getElementById(id))
}
JS;

        $content = preg_replace_callback(
            '/(jQuery|\$)\(\s*["\']#([\w\\\.-]+)["\']\s*\)/',
            function ($matches) {
                return "jQuerySelectorSpeedhack('" . str_replace('\\\\', '', $matches[2]) . "')";
            },
            $content
        );

        $content = preg_replace_callback(
            '/(jQuery|\$)\(\s*["\']#([\w\\\.-]+) ([ \w!"#$%&\'\(\)*+.\/:;<=>\?@\[\\\\\]^`\{\|\}~-]+)["\']\s*\)/',
            function ($matches) {
                return "jQuerySelectorSpeedhack('" . str_replace('\\\\', '', $matches[2]) . "').find('{$matches[3]}')";
            },
            $content
        );

        return $selector . $content;
    }
}

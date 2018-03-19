<?php

namespace Test\Assets\Closure;

use Neutrino\Assets\Closure\JqueryIdPrecompilation;

class JqueryIdPrecompilationTest extends \PHPUnit\Framework\TestCase
{
    public function dataPrecompile()
    {
        $speedHack = <<<JS
/**
* @param {string} id
* @return {(*|Window|Document|Element|Array<Element>|string|NodeList)}
*/
function jQuerySelectorSpeedhack(id) {
    return jQuery(document.getElementById(id))
}
JS;

        return [
          [$speedHack . "jQuerySelectorSpeedhack('someid')", "jQuery('#someid')"],
          [$speedHack . "jQuerySelectorSpeedhack('someid')", "$('#someid')"],
          [$speedHack . "jQuerySelectorSpeedhack('someid').find('.test')", "$('#someid .test')"],
          [$speedHack . "jQuerySelectorSpeedhack('someid').find('li[data=\"test\"]')", "$('#someid li[data=\"test\"]')"],
          [$speedHack . "jQuerySelectorSpeedhack('someid').find('.item span:not(.selected)')", "$('#someid .item span:not(.selected)')"],
          [$speedHack . "$('#someid, #otherid')", "$('#someid, #otherid')"],
        ];

    }
    /**
     * @dataProvider dataPrecompile
     *
     *  jQuery('#someid')   > jQuerySelectorSpeedhack('someid')
     *  $('#someid')        > jQuerySelectorSpeedhack('someid')
     *
     *  $('#someid .test')                      > jQuerySelectorSpeedhack('someid').find('.test')
     *  $('#someid li[data="test"]')            > jQuerySelectorSpeedhack('someid').find('li[data="test"]')
     *  $('#someid .item > span:not(.selected)')  > jQuerySelectorSpeedhack('someid').find('.item span:not(.selected)')
     */
    public function testPrecompile($expected, $input)
    {
        $precompiler = new JqueryIdPrecompilation([]);

        $this->assertEquals($expected, $precompiler->precompile($input));
    }
}

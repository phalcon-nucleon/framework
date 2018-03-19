<?php

namespace Test\Assets\Closure;

use Neutrino\Assets\Closure\GlobalClosurePrecompilation;

class GlobalClosurePrecompilationTest extends \PHPUnit_Framework_TestCase
{
    public function testPrecompile()
    {
        $precompiler = new GlobalClosurePrecompilation([
          'window' => 'window',
          'document' => 'document'
        ]);

        $this->assertEquals('(function(window,document){
  function aa(){}
})(window,document);', $precompiler->precompile('function aa(){}'));
    }
}

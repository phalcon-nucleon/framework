<?php

namespace Test\Assets\Closure;

use Neutrino\Assets\Closure\DebugPrecompilation;

class DebugPrecompilationTest extends \PHPUnit\Framework\TestCase
{
    public function testPrecompile()
    {
        $precompiler = new DebugPrecompilation(['debug' => true]);

        $this->assertEquals('/**
* @param {...*} _arg
*/
function debug(_arg){
    console.log.apply(console, arguments);
}function aa(){}', $precompiler->precompile('function aa(){}'));

        $precompiler = new DebugPrecompilation(['debug' => false]);

        $this->assertEquals('/**
* @param {...*} _arg
*/
function debug(_arg){}function aa(){}', $precompiler->precompile('function aa(){}'));
    }
}

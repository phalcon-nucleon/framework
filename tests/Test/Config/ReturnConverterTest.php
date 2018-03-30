<?php

namespace Test\Config;

use Neutrino\Config\ReturnConverter;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PHPUnit\Framework\TestCase;

class ReturnConverterTest extends TestCase
{
    public function testReturnConverter()
    {
        $visitor = new ReturnConverter('config');

        $visitor->setName('test');

        $varTest = new Variable('test');

        $nodes = $visitor->afterTraverse([
            new Assign($varTest, new String_('test')),
            new Return_(new Variable('test'))
        ]);

        $this->assertEquals([
            new Assign($varTest, new String_('test')),
            new Assign(new ArrayDimFetch(
                new Variable('config'),
                new String_('test')
            ), $varTest)
        ], $nodes);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Last statement must be the return of config.
     */
    public function testReturnConverterThrowException()
    {
        $visitor = new ReturnConverter('config');

        $visitor->setName('test');

        $nodes = $visitor->afterTraverse([
            new Assign(new Variable('test'), new String_('test')),
        ]);
    }
}

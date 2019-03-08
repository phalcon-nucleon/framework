<?php

namespace Test\Preloader\Visitors;

use Neutrino\PhpPreloader\Visitors\ArrayShortConverterVisitor;
use PhpParser\Node\Expr\Array_;
use PHPUnit\Framework\TestCase;

class ArrayShortConverterVisitorTest extends TestCase
{
    public function testArrayShortConverterVisitor()
    {
        $visitor = new ArrayShortConverterVisitor();

        $node = new \PhpParser\Node\Expr\Clone_(new \PhpParser\Node\Expr\Array_);
        $nnode = clone $node;
        $visitor->leaveNode($node);
        $this->assertEquals($nnode, $node);

        $node = new \PhpParser\Node\Expr\Array_();
        $nnode = clone $node;
        $visitor->leaveNode($node);
        $this->assertNotEquals($nnode, $node);
        $this->assertEquals(Array_::KIND_SHORT, $node->getAttribute('kind'));
    }
}

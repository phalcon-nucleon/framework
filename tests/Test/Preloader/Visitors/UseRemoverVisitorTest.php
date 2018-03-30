<?php

namespace Test\Preloader\Visitors;

use Neutrino\PhpPreloader\Visitors\UseRemoverVisitor;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeTraverser;
use PHPUnit\Framework\TestCase;

class UseRemoverVisitorTest extends TestCase
{
    public function testUseRemoverVisitor()
    {
        $visitor = new UseRemoverVisitor;

        $this->assertEquals(NodeTraverser::REMOVE_NODE, $visitor->leaveNode(new Use_([])));

        $this->assertEmpty(NodeTraverser::REMOVE_NODE, $visitor->leaveNode(new Array_()));
    }
}

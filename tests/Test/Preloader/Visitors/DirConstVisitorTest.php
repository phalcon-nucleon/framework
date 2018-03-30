<?php

namespace Test\Preloader\Visitors;

use Neutrino\PhpPreloader\Visitors\DirConstVisitor;
use PHPUnit\Framework\TestCase;

class DirConstVisitorTest extends TestCase
{
    public function testDirConstVisitor()
    {
        $visitor = new DirConstVisitor();

        $visitor->enterNode(new \PhpParser\Node\Scalar\MagicConst\Class_);
    }

    /**
     * @expectedException \Neutrino\PhpPreloader\Exceptions\DirConstantException
     */
    public function testDirConstVisitorThrowException()
    {
        $visitor = new DirConstVisitor();

        $visitor->enterNode(new \PhpParser\Node\Scalar\MagicConst\Dir());
    }
}

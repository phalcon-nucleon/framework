<?php

namespace Test\Preloader\Visitors;

use Neutrino\PhpPreloader\Visitors\DirConstVisitor;
use PHPUnit\Framework\TestCase;

class DirConstVisitorTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testDirConstVisitor()
    {
        $visitor = new DirConstVisitor();

        $visitor->enterNode(new \PhpParser\Node\Scalar\MagicConst\Class_);
    }

    public function testDirConstVisitorThrowException()
    {
        $this->expectException(\Neutrino\PhpPreloader\Exceptions\DirConstantException::class);
        $visitor = new DirConstVisitor();

        $visitor->enterNode(new \PhpParser\Node\Scalar\MagicConst\Dir());
    }
}

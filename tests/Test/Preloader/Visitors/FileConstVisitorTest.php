<?php

namespace Test\Preloader\Visitors;

use Neutrino\PhpPreloader\Visitors\FileConstVisitor;
use PHPUnit\Framework\TestCase;

class FileConstVisitorTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testFileConstVisitor()
    {
        $visitor = new FileConstVisitor();

        $visitor->enterNode(new \PhpParser\Node\Scalar\MagicConst\Class_);
    }

    public function testFileConstVisitorThrowException()
    {
        $this->expectException(\Neutrino\PhpPreloader\Exceptions\FileConstantException::class);
        $visitor = new FileConstVisitor();

        $visitor->enterNode(new \PhpParser\Node\Scalar\MagicConst\File());
    }
}

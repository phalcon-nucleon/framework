<?php

namespace Test\Preloader\Visitors;

use Neutrino\PhpPreloader\Visitors\FileConstVisitor;
use PHPUnit\Framework\TestCase;

class FileConstVisitorTest extends TestCase
{
    public function testFileConstVisitor()
    {
        $visitor = new FileConstVisitor();

        $visitor->enterNode(new \PhpParser\Node\Scalar\MagicConst\Class_);
    }

    /**
     * @expectedException \Neutrino\PhpPreloader\Exceptions\FileConstantException
     */
    public function testFileConstVisitorThrowException()
    {
        $visitor = new FileConstVisitor();

        $visitor->enterNode(new \PhpParser\Node\Scalar\MagicConst\File());
    }
}

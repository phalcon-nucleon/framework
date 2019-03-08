<?php

namespace Neutrino\PhpPreloader\Visitors;

use Neutrino\PhpPreloader\Exceptions\FileConstantException;
use PhpParser\Node;
use PhpParser\Node\Scalar\MagicConst\File as FileNode;
use PhpParser\NodeVisitorAbstract;

/**
 * This is the file node visitor class.
 *
 * Throw an exception if __FILE__ const usage find.
 */
class FileConstVisitor extends NodeVisitorAbstract
{
    /**
     * Enter and modify the node.
     *
     * @param \PhpParser\Node $node
     *
     * @throws \Neutrino\PhpPreloader\Exceptions\FileConstantException
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof FileNode) {
            throw new FileConstantException();
        }
    }
}

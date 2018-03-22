<?php

namespace Neutrino\PhpParser;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Class UseRemove
 *
 * @package Neutrino\PhpParser
 */
class UseRemover extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof Stmt\Use_) {
            // remove use nodes altogether
            return NodeTraverser::REMOVE_NODE;
        }
    }
}

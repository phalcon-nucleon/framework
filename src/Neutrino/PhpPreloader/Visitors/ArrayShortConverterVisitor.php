<?php

namespace Neutrino\PhpPreloader\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Class ArrayConvertShortVisitor
 *
 * @package Neutrino\PhpPreloader\Visitors
 */
class ArrayShortConverterVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        parent::leaveNode($node);

        if ($node instanceof Node\Expr\Array_) {
            $node->setAttribute('kind', Node\Expr\Array_::KIND_SHORT);
        }
    }
}

<?php

/*
 * This file is part of Class Preloader.
 *
 * (c) Graham Campbell <graham@alt-three.com>
 * (c) Michael Dowling <mtdowling@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Neutrino\PhpPreloader\Visitors;

use Neutrino\PhpPreloader\Exceptions\DirConstantException;
use PhpParser\Node;
use PhpParser\Node\Scalar\MagicConst\Dir as DirNode;
use PhpParser\NodeVisitorAbstract;

/**
 * This is the directory node visitor class.
 *
 * Throw an exception if __DIR__ const usage find.
 */
class DirConstVisitor extends NodeVisitorAbstract
{
    /**
     * Enter and modify the node.
     *
     * @param \PhpParser\Node $node
     *
     * @throws \Neutrino\PhpPreloader\Exceptions\DirConstantException
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof DirNode) {
            throw new DirConstantException();
        }
    }
}

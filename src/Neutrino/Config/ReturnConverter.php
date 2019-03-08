<?php

namespace Neutrino\Config;

use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;

/**
 * Class ReturnTransformer
 *
 * @package Neutrino\Config
 */
class ReturnConverter extends NodeVisitorAbstract
{
    private $name;

    private $variable;

    public function __construct($variable)
    {
        $this->variable = $variable;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param array $nodes
     *
     * @return \PhpParser\Node[]
     * @throws \Exception
     */
    public function afterTraverse(array $nodes)
    {
        $last = $nodes[count($nodes) - 1];

        if (!($last instanceof Return_)) {
            throw new \Exception('Last statement must be the return of config.');
        }

        $exprAssign = new Assign(
            new ArrayDimFetch(
                new Variable($this->variable),
                new String_($this->name)
            ),
            $last->expr
        );

        $nodes[count($nodes) - 1] = $exprAssign;

        return $nodes;
    }
}

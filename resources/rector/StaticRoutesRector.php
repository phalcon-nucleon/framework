<?php

declare(strict_types=1);

namespace Nucleon\Rector;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitor;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Nucleon 2.0: `RoutesTestCase::routes()` is static (a PHPUnit 11 data provider). Its calls on `$this` become
 * calls on `static`.
 */
final class StaticRoutesRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Makes routes() of the Nucleon RoutesTestCase static', [
            new CodeSample(
                'protected function routes() { return [$this->formatDataRoute(\'/\', \'GET\', true, \'Index\', \'index\')]; }',
                'protected static function routes() { return [static::formatDataRoute(\'/\', \'GET\', true, \'Index\', \'index\')]; }',
            ),
        ]);
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isObjectType($node, new ObjectType('Neutrino\Test\RoutesTestCase'))) {
            return null;
        }

        $method = $node->getMethod('routes');

        if ($method === null || $method->isStatic()) {
            return null;
        }

        $method->flags |= Modifiers::STATIC;

        $this->traverseNodesWithCallable((array) $method->stmts, static function (Node $node): int|Node|null {
            // A closure keeps its own $this.
            if ($node instanceof Node\Expr\Closure || $node instanceof Node\Stmt\Class_) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if ($node instanceof MethodCall && $node->var instanceof Variable && $node->var->name === 'this' && $node->name instanceof Identifier) {
                return new StaticCall(new Name('static'), $node->name, $node->args);
            }

            return null;
        });

        return $node;
    }
}

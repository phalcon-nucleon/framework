<?php

declare(strict_types=1);

namespace Nucleon\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Type\ObjectType;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\Value\ValueResolver;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Nucleon 2.0 types the properties of the classes the applications extend: a redeclaration must have the same
 * type. Without a default value, the 1.3 redeclaration (`protected $options;`) would leave the typed property
 * uninitialized: the default value of the parent is added.
 */
final class TypedPropertiesRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var list<array{string, string, string, Expr|null}> [parent class, property, type, default value] */
    private array $properties = [];

    public function __construct(private readonly ValueResolver $valueResolver) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Types the properties of the Nucleon classes, with the default value of the parent', [
            new ConfiguredCodeSample(
                'class Example extends SimpleProvider { protected $options; }',
                'class Example extends SimpleProvider { protected array $options = []; }',
                [['Neutrino\Support\SimpleProvider', 'options', 'array', '[]']],
            ),
        ]);
    }

    /**
     * @param array<array{string, string, string, string|null}> $configuration [parent class, property, type, default value
     *                                                                         as PHP, `null` when the parent has none]
     */
    public function configure(array $configuration): void
    {
        $this->properties = [];

        foreach ($configuration as [$class, $property, $type, $default]) {
            if ($default === null) {
                $this->properties[] = [$class, $property, $type, null];

                continue;
            }

            $expression = (new \PhpParser\ParserFactory())->createForHostVersion()->parse('<?php ' . $default . ';')[0] ?? null;

            if (!$expression instanceof Node\Stmt\Expression) {
                throw new \InvalidArgumentException('Invalid default value "' . $default . '".');
            }

            $this->properties[] = [$class, $property, $type, $expression->expr];
        }
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
        $changed = false;

        foreach ($node->getProperties() as $declaration) {
            if ($declaration->type !== null || $declaration->isStatic() || count($declaration->props) !== 1) {
                continue;
            }

            $name = $declaration->props[0]->name->toString();

            foreach ($this->properties as [$class, $property, $type, $default]) {
                if ($property !== $name || !$this->isObjectType($node, new ObjectType($class))) {
                    continue;
                }

                $declaration->type = str_starts_with($type, '?') ? new Node\NullableType(new Node\Identifier(substr($type, 1))) : new Node\Identifier($type);
                // `$x = null` on a non nullable type, or no default: the default of the parent.
                $value = $declaration->props[0]->default;
                if ($default !== null && ($value === null || ($this->valueResolver->isNull($value) && !str_starts_with($type, '?')))) {
                    $declaration->props[0]->default = clone $default;
                }

                $changed = true;

                break;
            }
        }

        return $changed ? $node : null;
    }
}

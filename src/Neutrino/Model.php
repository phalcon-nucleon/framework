<?php

declare(strict_types=1);

namespace Neutrino;

use Neutrino\Model\Attribute;
use Neutrino\Model\Description;
use Neutrino\Model\MetaDataStrategy;
use Phalcon\Db\Column;
use Phalcon\Mvc\Model\Behavior\SoftDelete;
use Phalcon\Mvc\Model\Behavior\Timestampable;
use ReflectionClass;
use RuntimeException;

/**
 * A model described without database introspection, in `initialize()` or with attributes:
 *
 * ```php
 * #[Timestamps]
 * class User extends Model
 * {
 *     #[Primary] public ?int $id = null;
 *     #[Column(Column::TYPE_VARCHAR)] public string $email;
 *
 *     public function initialize()
 *     {
 *         parent::initialize();
 *         $this->setSource('users');
 *         $this->column('name', Column::TYPE_VARCHAR, ['nullable' => true]);
 *     }
 * }
 * ```
 *
 * The description reaches Phalcon through {@see MetaDataStrategy}, set on the `modelsMetadata` service by the
 * `Model` / `ModelsMetaData` providers. It is built in `initialize()`, once per request and model: about 10 µs
 * more with attributes (reflection) than with the methods.
 *
 * @extends \Phalcon\Mvc\Model<mixed>
 */
abstract class Model extends \Phalcon\Mvc\Model
{
    /** @var array<class-string, Description> */
    private static array $descriptions = [];

    /**
     * Starts the description of the model: overrides call it first.
     *
     * Not typed on the return: the 1.3 models override it untyped.
     *
     * @return void
     */
    public function initialize()
    {
        self::$descriptions[static::class] = new Description();

        $this->describeFromAttributes();
    }

    public static function description(): Description
    {
        return self::$descriptions[static::class] ?? throw new RuntimeException(static::class . ' is not described: call parent::initialize() first.');
    }

    /**
     * @param array{map?: string, identity?: bool, autoIncrement?: bool} $options
     */
    protected function primary(string $name, ?int $type, array $options = []): void
    {
        static::description()->primary($name, $type, $options);
    }

    /**
     * @param array{map?: string, nullable?: bool, default?: mixed, autoInsert?: bool, autoUpdate?: bool} $options
     */
    protected function column(string $name, ?int $type, array $options = []): void
    {
        static::description()->column($name, $type, $options);
    }

    /**
     * A column set on create (`insert`) and/or on update (`update`), with the Timestampable behavior.
     *
     * @param array{type?: int, format?: string, insert?: bool, update?: bool, default?: mixed, nullable?: bool, map?: string, autoInsert?: bool, autoUpdate?: bool} $options
     */
    protected function timestampable(string $name, array $options = []): void
    {
        if (($options['autoInsert'] ?? false) || ($options['autoUpdate'] ?? false)) {
            throw new RuntimeException('Model: a timestampable field can\'t have autoInsert or autoUpdate.');
        }

        $format = $options['format'] ?? DATE_ATOM;
        $params = [];

        if (!isset($options['default']) && ($options['insert'] ?? false)) {
            $params['beforeValidationOnCreate'] = ['field' => $name, 'format' => $format];
        }
        if ($options['update'] ?? false) {
            $params['beforeValidationOnUpdate'] = ['field' => $name, 'format' => $format];
        }
        if ($params === []) {
            throw new RuntimeException('Model: a timestampable field needs to have at least insert or update.');
        }

        $this->column($name, $options['type'] ?? Column::TYPE_DATETIME, array_intersect_key($options, ['map' => 1, 'nullable' => 1, 'default' => 1]));

        $this->addBehavior(new Timestampable($params));
    }

    /**
     * `created_at` (set on create) and `updated_at` (set on create and on update) columns: the model writes both,
     * as the NOT NULL columns of the schema builder's `timestamps()` need.
     */
    protected function timestamps(string $createdAt = 'created_at', string $updatedAt = 'updated_at', string $format = DATE_ATOM): void
    {
        $this->timestampable($createdAt, ['insert' => true, 'format' => $format]);
        $this->timestampable($updatedAt, ['insert' => true, 'update' => true, 'format' => $format, 'nullable' => true]);
    }

    /**
     * A column marking the row as deleted, with the SoftDelete behavior.
     *
     * @param array{type?: int, value?: mixed, default?: mixed, nullable?: bool, map?: string, autoInsert?: bool, autoUpdate?: bool} $options
     */
    protected function softDeletable(string $name, array $options = []): void
    {
        if (($options['autoInsert'] ?? false) || ($options['autoUpdate'] ?? false)) {
            throw new RuntimeException('Model: a soft delete field can\'t have autoInsert or autoUpdate.');
        }

        $type = $options['type'] ?? Column::TYPE_BOOLEAN;
        // Not deleted by default: the column is not null.
        $options += ['default' => $type === Column::TYPE_BOOLEAN ? false : null];

        $this->column($name, $type, array_intersect_key($options, ['map' => 1, 'nullable' => 1, 'default' => 1]));

        $this->addBehavior(new SoftDelete(['field' => $name, 'value' => $options['value'] ?? true]));
    }

    /**
     * A `deleted` soft delete column.
     */
    protected function softDelete(string $name = 'deleted'): void
    {
        $this->softDeletable($name);
    }

    private function describeFromAttributes(): void
    {
        $class = new ReflectionClass($this);

        foreach ($class->getProperties() as $property) {
            // The properties of Phalcon\Mvc\Model (about 25) never carry a column.
            if ($property->class === \Phalcon\Mvc\Model::class) {
                continue;
            }

            foreach ($property->getAttributes(Attribute\Primary::class) as $attribute) {
                $primary = $attribute->newInstance();
                $this->primary($primary->name ?? $property->getName(), $primary->type, [
                    'map'           => $property->getName(),
                    'identity'      => $primary->identity,
                    'autoIncrement' => $primary->autoIncrement,
                ]);
            }

            foreach ($property->getAttributes(Attribute\Column::class) as $attribute) {
                $column = $attribute->newInstance();
                $this->column($column->name ?? $property->getName(), $column->type, [
                    'map'        => $property->getName(),
                    'nullable'   => $column->nullable,
                    'default'    => $column->default,
                    'autoInsert' => $column->autoInsert,
                    'autoUpdate' => $column->autoUpdate,
                ]);
            }
        }

        foreach ($class->getAttributes(Attribute\Timestamps::class) as $attribute) {
            $timestamps = $attribute->newInstance();
            $this->timestamps($timestamps->createdAt, $timestamps->updatedAt, $timestamps->format);
        }

        foreach ($class->getAttributes(Attribute\SoftDelete::class) as $attribute) {
            $softDelete = $attribute->newInstance();
            $this->softDeletable($softDelete->column, ['value' => $softDelete->value]);
        }
    }
}

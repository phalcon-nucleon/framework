<?php

declare(strict_types=1);

namespace Neutrino\Model;

use Neutrino\Model;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\Model\MetaData\Strategy\Introspection;
use Phalcon\Mvc\Model\MetaData\Strategy\StrategyInterface;
use Phalcon\Mvc\ModelInterface;

/**
 * Meta-data of the {@see Model} subclasses from their description, without querying the database. The other
 * models go to `$fallback` (introspection by default).
 */
final class MetaDataStrategy implements StrategyInterface
{
    /**
     * @param StrategyInterface|null $fallback Built on first use: Introspection by default
     */
    public function __construct(private ?StrategyInterface $fallback = null) {}

    /**
     * @param ModelInterface<mixed> $model
     *
     * @return array<int, array<string, string>|null>
     */
    public function getColumnMaps(ModelInterface $model, DiInterface $container): array
    {
        /** @var array<int, array<string, string>|null> */
        return $model instanceof Model ? $model::description()->columnMaps() : $this->fallback()->getColumnMaps($model, $container);
    }

    /**
     * @param ModelInterface<mixed> $model
     *
     * @return array<int, mixed>
     */
    public function getMetaData(ModelInterface $model, DiInterface $container): array
    {
        /** @var array<int, mixed> */
        return $model instanceof Model ? $model::description()->metaData() : $this->fallback()->getMetaData($model, $container);
    }

    private function fallback(): StrategyInterface
    {
        // A Phalcon class is slow to build the first time in a process: only when a model needs it.
        return $this->fallback ??= new Introspection();
    }
}

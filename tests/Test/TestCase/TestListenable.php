<?php

declare(strict_types=1);

namespace Test\TestCase;

/**
 * Records the calls of a listener or a middleware.
 */
interface TestListenable
{
    /**
     * @param array<int, mixed>|null $data
     */
    public function view(string $seek, ?array $data = null): void;

    public function hasView(string $seek): bool;

    /**
     * @return list<array<int, mixed>|null>
     */
    public function getView(string $seek): array;
}

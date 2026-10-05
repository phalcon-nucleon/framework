<?php

declare(strict_types=1);

namespace Test\TestCase;

/**
 * Implementation of {@see TestListenable}.
 */
trait TestListenize
{
    /** @var array<string, list<array<int, mixed>|null>> */
    public array $views = [];

    /**
     * @param array<int, mixed>|null $data
     */
    public function view(string $seek, ?array $data = null): void
    {
        $this->views[$seek][] = $data;
    }

    public function hasView(string $seek): bool
    {
        return isset($this->views[$seek]);
    }

    /**
     * @return list<array<int, mixed>|null>
     */
    public function getView(string $seek): array
    {
        return $this->views[$seek] ?? [];
    }
}

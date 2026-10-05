<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Optimize;

final class PreloadResult
{
    /**
     * @param list<string>          $preloaded Classes listed in the preload script.
     * @param array<string, string> $skipped   Classes that could not be loaded, with the reason.
     */
    public function __construct(
        public readonly string $file,
        public readonly array $preloaded,
        public readonly array $skipped,
    ) {}
}

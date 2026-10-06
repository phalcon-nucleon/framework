<?php

declare(strict_types=1);

namespace Neutrino\Database\Migrations\Prefix;

/**
 * Prefix of the migration files, which orders them.
 */
interface PrefixInterface
{
    /**
     * A new prefix.
     */
    public function getPrefix(): string;

    /**
     * The name without its prefix.
     */
    public function deletePrefix(string $str, string $delimiter = '_'): string;
}

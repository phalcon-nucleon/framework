<?php

declare(strict_types=1);

namespace Neutrino\Database\Migrations\Prefix;

/**
 * `1511357112_create_users_table`.
 */
final class TimestampPrefix implements PrefixInterface
{
    public function getPrefix(): string
    {
        return (string) time();
    }

    public function deletePrefix(string $str, string $delimiter = '_'): string
    {
        if ($delimiter === '') {
            return $str;
        }

        return implode($delimiter, array_slice(explode($delimiter, $str), 1));
    }
}

<?php

declare(strict_types=1);

namespace Neutrino\Database\Migrations\Prefix;

/**
 * `2017_11_22_134512_create_users_table`.
 */
final class DatePrefix implements PrefixInterface
{
    public function getPrefix(): string
    {
        return date('Y_m_d_His');
    }

    public function deletePrefix(string $str, string $delimiter = '_'): string
    {
        if ($delimiter === '') {
            return $str;
        }

        return implode($delimiter, array_slice(explode($delimiter, $str), 4));
    }
}

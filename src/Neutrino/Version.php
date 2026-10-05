<?php

declare(strict_types=1);

namespace Neutrino;

/**
 * Version of the Nucleon framework.
 */
final class Version
{
    public const int MAJOR = 2;

    public const int MINOR = 0;

    public const int PATCH = 0;

    /** Empty for a stable release, otherwise `dev`, `alpha1`, `beta2`, `RC1`… */
    public const string STABILITY = 'dev';

    /**
     * Semantic version, e.g. `2.0.0` or `2.0.0-dev`.
     */
    public static function get(): string
    {
        return rtrim(self::MAJOR . '.' . self::MINOR . '.' . self::PATCH . '-' . self::STABILITY, '-');
    }

    /**
     * Comparable numeric identifier, e.g. `20000` for 2.0.0 (major, minor and patch on two digits).
     */
    public static function getId(): string
    {
        return sprintf('%d%02d%02d', self::MAJOR, self::MINOR, self::PATCH);
    }
}

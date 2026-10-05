<?php

declare(strict_types=1);

namespace Neutrino\Cli\Attribute;

use Attribute;

/**
 * Description of a task action, shown by `list` and `help`.
 *
 * Attributes are kept by OPcache even with `opcache.save_comments=0`, unlike docblocks.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Description
{
    public function __construct(public readonly string $text) {}
}

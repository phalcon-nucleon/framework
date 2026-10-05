<?php

declare(strict_types=1);

namespace Neutrino\Constants\Events;

final class Kernel
{
    public const string BOOT = 'kernel:boot';

    public const string TERMINATE = 'kernel:terminate';
}

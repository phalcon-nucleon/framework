<?php

declare(strict_types=1);

namespace Neutrino\Interfaces;

/**
 * A provider registers one or more services in the container when the kernel boots.
 */
interface Providable
{
    /**
     * Called while the kernel registers its services. Registers the definitions only:
     * the services themselves are built when they are first resolved.
     */
    public function registering(): void;
}

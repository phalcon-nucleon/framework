<?php

declare(strict_types=1);

namespace Neutrino\Process\Exception;

use Neutrino\Process\Process;

/**
 * A process still running after its timeout.
 */
final class ProcessTimedOutException extends ProcessException
{
    public function __construct(private readonly Process $process, float $timeout)
    {
        parent::__construct(sprintf('The command "%s" exceeded the timeout of %s seconds.', $process->getCommandLine(), $timeout));
    }

    public function getProcess(): Process
    {
        return $this->process;
    }
}

<?php

declare(strict_types=1);

namespace Neutrino\Process\Exception;

use Neutrino\Process\Process;

/**
 * A process that exited with a code other than 0 ({@see Process::mustRun()}).
 */
final class ProcessFailedException extends ProcessException
{
    public function __construct(private readonly Process $process)
    {
        parent::__construct(sprintf(
            "The command \"%s\" failed with exit code %d.\n\nOutput:\n%s\n\nError output:\n%s",
            $process->getCommandLine(),
            $process->getExitCode() ?? -1,
            $process->getOutput(),
            $process->getErrorOutput(),
        ), $process->getExitCode() ?? 0);
    }

    public function getProcess(): Process
    {
        return $this->process;
    }
}

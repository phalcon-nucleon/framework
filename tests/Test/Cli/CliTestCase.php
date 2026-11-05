<?php

declare(strict_types=1);

namespace Test\Cli;

use Fake\Kernels\Cli\Output\StubOutput;
use Fake\Kernels\Cli\StubKernelCli;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Constants\Services;
use Test\TestCase\TestCase;

/**
 * Runs commands through the fake app's console kernel, with the output kept in memory and no colors.
 */
abstract class CliTestCase extends TestCase
{
    protected StubOutput $output;

    protected static function kernelClassInstance(): string
    {
        return StubKernelCli::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Decorate::setColorSupport(false);

        $this->output = new StubOutput();
        $this->mockService(Services\Cli::OUTPUT, $this->output);
    }

    protected function tearDown(): void
    {
        Decorate::setColorSupport(null);

        parent::tearDown();
    }

    /**
     * Runs a command line (without the script name) and returns the output.
     */
    protected function runCommand(string $command): string
    {
        $this->dispatchCli('nucleon ' . $command);

        return $this->output->out;
    }
}

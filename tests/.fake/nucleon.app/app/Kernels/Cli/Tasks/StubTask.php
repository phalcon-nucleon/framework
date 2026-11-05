<?php

declare(strict_types=1);

namespace Fake\Kernels\Cli\Tasks;

use Neutrino\Cli\Attribute\Argument;
use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Attribute\Option;
use Neutrino\Cli\Task;

class StubTask extends Task
{
    /** @var list<array{string, array<int|string, mixed>, array<int|string, mixed>}> */
    public static array $calls = [];

    public function onConstruct()
    {
        parent::onConstruct();
    }

    #[Description('StubTask::mainAction')]
    #[Argument('abc', 'abc Arg')]
    #[Argument('xyz', 'xyz Arg')]
    #[Option('-o1, --opt_1', 'Option one')]
    #[Option('-o2, --opt_2', 'Option two')]
    public function mainAction(): void
    {
        self::$calls[] = [__FUNCTION__, $this->getArgs(), $this->getOptions()];
    }

    /**
     * StubTask::testAction
     */
    public function testAction(): void
    {
        self::$calls[] = [__FUNCTION__, $this->getArgs(), $this->getOptions()];
    }

    /**
     * Documented with a docblock (deprecated).
     *
     * @description StubTask::legacyAction
     *
     * @argument name : the name
     *
     * @option -f, --force : force it
     */
    public function legacyAction(): void {}
}

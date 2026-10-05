<?php

namespace Bench\Tasks;

use Neutrino\Cli\Task;

class HelloTask extends Task
{
    public function mainAction()
    {
        $this->line('Hello');
    }
}

<?php

namespace RectorFixture;

use Neutrino\Debug\Reflexion;
use Neutrino\Process\Process;
use Neutrino\Process\Timeout;
use Phalcon\Validation\Validator\PresenceOf;

class Usage
{
    public function run(Process $process)
    {
        try {
            $process->exec();
        } catch (Timeout $e) {
            return $process->getError();
        }

        $validator = new PresenceOf();
        $di = \Phalcon\Di::getDefault();

        if ($di === null) {
            throw new \Phalcon\Exception('No container');
        }

        return Reflexion::get($process, 'pid') . $process->pid();
    }
}

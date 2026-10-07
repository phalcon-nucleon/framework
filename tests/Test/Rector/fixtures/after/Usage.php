<?php

namespace RectorFixture;

use Neutrino\Process\Process;

class Usage
{
    public function run(Process $process)
    {
        try {
            $process->run();
        } catch (\Neutrino\Process\Exception\ProcessTimedOutException $e) {
            return $process->getErrorOutput();
        }

        $validator = new \Phalcon\Filter\Validation\Validator\PresenceOf();
        $di = \Phalcon\Di\Di::getDefault();

        if ($di === null) {
            throw new \Exception('No container');
        }

        return \Neutrino\Support\Reflection::get($process, 'pid') . $process->getPid();
    }
}

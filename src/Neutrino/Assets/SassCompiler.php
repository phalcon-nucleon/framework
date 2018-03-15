<?php

namespace Neutrino\Assets;

use Neutrino\Assets\Exception\CompilatorException;
use Neutrino\Process\Exception;
use Neutrino\Process\Process;
use Phalcon\Di\Injectable;

/**
 * Class SassCompiler
 *
 * Neutrino\Assets
 */
class SassCompiler extends Injectable implements AssetsCompilator
{
    /**
     * @param array $options
     *
     * @return bool
     *
     * @throws CompilatorException
     */
    public function compile(array $options)
    {
        if (empty($options['sass_file'])) {
            throw new CompilatorException('sass_file option can\'t be empty.');
        }
        if (empty($options['output_file'])) {
            throw new CompilatorException('output_file option can\'t be empty.');
        }
        if (empty($options['sass_cmd'])) {
            $cmd = 'sass';
            $procOptions = ['bypass_shell' => false];
        } else {
            $cmd = $options['sass_cmd'];
            $procOptions = [];
        }

        $cmd = [$cmd, '"' . $options['sass_file'] . '"', '"' . $options['output_file'] . '"'];

        $cmd = array_merge($cmd, isset($options['cmd_options']) ? $options['cmd_options'] : []);

        $cwd = isset($options['base_path']) ? $options['base_path'] : BASE_PATH;

        /** @var Process $proc */
        $proc = $this->getDI()->get(Process::class, [implode(' ', $cmd), $cwd, $procOptions]);

        try {
            $proc->start();
        } catch (Exception $e) {
            throw new CompilatorException('Can\'t open process.');
        }

        $proc->wait();

        if (!empty($str = $proc->getOutput())) {
            throw new CompilatorException($str);
        }

        if (!empty($str = $proc->getError())) {
            throw new CompilatorException($str);
        }

        return true;
    }
}

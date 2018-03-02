<?php

namespace Neutrino\Assets;

use Neutrino\Assets\Exception\CompilatorException;

/**
 * Class SassCompiler
 *
 * Neutrino\Assets
 */
class SassCompiler implements AssetsCompilator
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

        $cmd = ['sass', '"' . $options['sass_file'] . '"', '"' . $options['output_file'] . '"'];

        $cmd = array_merge($cmd, $options['cmd_options'] ?? []);

        $desc = [
          ["pipe", "r+"],
          ["pipe", "w+"],
          ["pipe", "w+"]
        ];

        $proc = proc_open(implode(' ', $cmd), $desc, $pipes, $options['base_path'] ?? BASE_PATH);

        if(!is_resource($proc)) {
            throw new CompilatorException('Can\'t open process.');
        }

        while (proc_get_status($proc)['running'] ?? false) {
            sleep(1);
        }

        if (!empty($str = stream_get_contents($pipes[1]))) {
            throw new CompilatorException($str);
        }

        if (!empty($str = stream_get_contents($pipes[2]))) {
            throw new CompilatorException($str);
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return true;
    }
}

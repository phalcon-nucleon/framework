<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Task;

/**
 * Class ServerTask
 *
 * App\Kernels\Cli\Tasks
 */
class ServerTask extends Task
{
    /**
     * Runs a local web server
     *
     * @option --ip   : Define the ip to use. Default : 127.0.0.1.
     * @option --port : Define the port to use. Default : 8000.
     */
    public function mainAction()
    {
        $ip = $this->getOption('ip', '127.0.0.1');

        if($this->hasOption('port')){
            $port = $this->getOption('port');

            if ($this->portIsOpen($ip, $port)) {
                $this->block(['Port [' . $port . '] on ip [' . $ip . '] is already used.'], 'error');
                return;
            }
        } else {
            $port = $this->acquierePort($ip);
        }

        $this->run($ip, $port);
    }

    private function run($ip, $port)
    {
        $proc = proc_open(PHP_BINARY . ' -S ' . $ip . ':' . $port . ' app_dev.php', [], $pipes, BASE_PATH . '/public');

        if (!(is_resource($proc) && $this->isRunning($proc))) {
            $this->block(['Can\'t run server'], 'error');
            return;
        }

        $this->block(['[OK] http://' . $ip . ':' . $port], 'info');

        do {
            sleep(1);
        } while ($this->isRunning($proc));

        proc_terminate($proc);

        proc_close($proc);
    }

    private function isRunning($proc)
    {
        $status = proc_get_status($proc);

        return (isset($status['running']) ? $status['running'] : false) === true;
    }


    private function acquierePort($ip)
    {
        $port = 8000;

        while ($this->portIsOpen($ip, $port)) $port++;

        return $port;
    }

    private function portIsOpen($ip, $port)
    {
        $fp = @fsockopen($ip, $port, $errno, $errstr, 0.1);
        if (!$fp) {
            return false;
        } else {
            fclose($fp);
            return true;
        }
    }
}

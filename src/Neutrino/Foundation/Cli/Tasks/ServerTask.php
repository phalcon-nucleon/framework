<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Task;
use Neutrino\Process\Exception;
use Neutrino\Process\Process;

/**
 * Class ServerTask
 *
 * App\Kernels\Cli\Tasks
 */
class ServerTask extends Task
{
    /** @var Process */
    private $proc;

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
            $port = $this->acquirePort($ip);
        }

        $this->run($ip, $port);
    }

    private function run($ip, $port)
    {
        $cmd = PHP_BINARY . ' -S ' . $ip . ':' . $port . ' app_dev.php';
        $cwd = BASE_PATH . '/public';

        $this->proc = $this->getDI()->get(Process::class, [$cmd, $cwd]);

        try{
            $this->proc->start();
        } catch (Exception $e){
            $this->block(['Can\'t run server'], 'error');
            return;
        }

        $this->block(['[OK] http://' . $ip . ':' . $port], 'info');

        $this->proc->wait();

        $this->block(['[ERR] server suddenly stopped'], 'error');

        $this->proc->close();
    }

    private function acquirePort($ip)
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

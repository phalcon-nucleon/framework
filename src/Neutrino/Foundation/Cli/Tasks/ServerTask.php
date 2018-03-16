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
    const IP_PATTERN = '/^(?:(?:\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])$/';

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
        try {
            $ip = $this->getIp();
            $port = $this->getPort($ip);

            $this->run($ip, $port);
        } catch (\Exception $e) {
            $this->block([$e->getMessage()], 'error');
            return;
        }
    }

    /**
     * @return null|string
     * @throws \Exception
     */
    private function getIp()
    {
        $ip = $this->getOption('ip', '127.0.0.1');

        if (empty($ip) || true === $ip) {
            throw new \Exception('IP can\'t be empty');
        }

        if(!preg_match(self::IP_PATTERN, $ip)){
            throw new \Exception('['.$ip.'] is not a valid ip');
        }

        return $ip;
    }

    /**
     * @param $ip
     * @return int|null|string
     * @throws \Exception
     */
    private function getPort($ip)
    {
        if ($this->hasOption('port')) {
            $port = $this->getOption('port');

            if(empty($port) || true === $port){
                throw new \Exception('Port can\'t be empty');
            }
            if ($this->portIsOpen($ip, $port)) {
                throw new \Exception('Port [' . $port . '] on ip [' . $ip . '] is already used.');
            }
        } else {
            $port = $this->acquirePort($ip);
        }

        return $port;
    }

    /**
     * @param $ip
     * @param $port
     * @throws Exception
     */
    private function run($ip, $port)
    {
        $cmd = PHP_BINARY . ' -S ' . $ip . ':' . $port . ' app_dev.php';
        $cwd = BASE_PATH . '/public';

        $this->proc = $this->getDI()->get(Process::class, [$cmd, $cwd]);

        $this->proc->start();

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

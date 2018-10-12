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
     * @option --host : Define the host or ip to use. Default : 127.0.0.1.
     * @option --port : Define the port to use. Default : 8000.
     */
    public function mainAction()
    {
        try {
            $host = $this->getHost();
            $port = $this->getPort($host);

            $this->run($host, $port);
        } catch (\Exception $e) {
            $this->block([$e->getMessage()], 'error');
            return;
        }
    }

    /**
     * @return null|string
     * @throws \Exception
     */
    private function getHost()
    {
        $host = $this->getOption('host', '127.0.0.1');

        if (empty($host) || true === $host) {
            throw new \Exception('Host can\'t be empty');
        }

        if (!(
            // IP validation
            filter_var($host, FILTER_VALIDATE_IP)
            // Domain validation
            || (PHP_VERSION_ID >= 70000 && filter_var($host, FILTER_VALIDATE_DOMAIN))
            || (PHP_VERSION_ID < 70000
                && preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $host) //valid chars check
                && preg_match("/^.{1,253}$/", $host) //overall length check
                && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $host))
        )) {
            throw new \Exception('Host [' . $host . '] is not valid.');
        }

        return $host;
    }

    /**
     * @param $host
     *
     * @return int|null|string
     * @throws \Exception
     */
    private function getPort($host)
    {
        if ($this->hasOption('port')) {
            $port = $this->getOption('port');

            if(empty($port) || true === $port){
                throw new \Exception('Port can\'t be empty');
            }
            if ($this->portIsOpen($host, $port)) {
                throw new \Exception('Port [' . $port . '] on host [' . $host . '] is already used.');
            }
        } else {
            $port = $this->acquirePort($host);
        }

        return $port;
    }

    /**
     * @param $host
     * @param $port
     *
     * @throws Exception
     */
    private function run($host, $port)
    {
        $cmd = PHP_BINARY . ' -S ' . $host . ':' . $port . ' app_dev.php';
        $cwd = BASE_PATH . '/public';

        $this->proc = $this->getDI()->get(Process::class, [$cmd, $cwd]);

        $this->proc->start();

        $this->block(['[OK] http://' . $host . ':' . $port], 'info');

        $this->proc->watch(function ($stdo, $stde) {
            !empty($stdo) && $this->line($stdo);
            !empty($stde) && $this->error($stde);
        });

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

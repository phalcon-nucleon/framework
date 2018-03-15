<?php

namespace Neutrino\Process;

/**
 * Class Process
 *
 * Neutrino\Process
 */
class Process
{
    private $proc;

    private $cmd;

    private $spec = [];

    private $pipes = [];

    private $cwd;

    private $options;

    private $status;

    private $content = '';

    private $error = '';

    public function __construct($cmd, $cwd = null, array $options = null)
    {
        $this->cmd = $cmd;
        $this->cwd = $cwd;
        $this->options = $options;
    }

    /**
     * Start a process
     *
     * @return $this
     * @throws Exception
     */
    public function start()
    {
        $this->spec = [
            // 0 => ['pipe', 'w+'],
            1 => fopen('php://temp/maxmemory:' . (1024 * 1024), 'w+'),
            2 => fopen('php://temp/maxmemory:' . (1024 * 1024), 'w+'),
        ];

        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->options = array_merge(['bypass_shell' => true], (array)$this->options);
        }

        $this->proc = proc_open($this->cmd, $this->spec, $this->pipes, $this->cwd, null, $this->options);

        $this->pipes = $this->spec;

        if (!$this->isRunning()) {
            throw new Exception('Can\'t create process');
        }

        return $this;
    }

    /**
     * Wait until process end
     *
     * @param int|null $timeout in ms
     * @param int      $step    in ms
     */
    public function wait($timeout = null, $step = 1000)
    {
        $withTimeout = false === is_null($timeout);

        if ($withTimeout) {
            $start = microtime(true);
            if ($timeout < $step) {
                $step = $timeout;
            }
        }

        while ($this->isRunning()) {
            usleep($step * 1000);

            if ($withTimeout && ($start + $timeout) > microtime(true)) {
                return;
            }
        }
    }

    /**
     * Stop a process
     *
     * @param int $timeout in ms
     *
     * @return $this
     */
    public function stop($timeout = 1000)
    {
        $timeout = microtime(true) + ($timeout * 1000);

        if ($this->isRunning()) {
            proc_terminate($this->proc);
        }
        while ($this->isRunning() && microtime(true) < $timeout) {
            usleep(1000);
        }

        $this->readOutput();
        $this->readError();

        return $this;
    }

    /**
     * Stop & Close the resources
     */
    public function close()
    {
        $this->stop(0);

        if (is_resource($this->proc)) {
            proc_close($this->proc);
        }

        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
    }

    /**
     * Execute a process
     *
     * @param int|null $timeout in ms
     *
     * @return bool
     */
    public function exec($timeout = null)
    {
        try {
            $this->start();
            $this->wait($timeout, 500);
        } catch (Exception $e) {
            return false;
        } finally {
            $this->close();
        }

        return true;
    }

    /**
     * Check if is running
     *
     * @return bool
     */
    public function isRunning()
    {
        return true === is_resource($this->proc) && true === $this->readStatus(true)['running'];
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        $this->readOutput();

        return $this->content;
    }

    /**
     * @return string
     */
    public function getError()
    {
        $this->readError();

        return $this->error;
    }

    /**
     * @return mixed
     */
    public function pid()
    {
        return $this->readStatus()['pid'];
    }

    /**
     * @param bool $fresh
     *
     * @return array|bool
     */
    public function readStatus($fresh = false)
    {
        if (!isset($this->status) || $fresh && is_resource($this->proc)) {
            $this->status = proc_get_status($this->proc);
        }

        return $this->status;
    }

    private function readOutput()
    {
        $this->content .= $this->read($this->pipes[1], strlen($this->content));
    }

    private function readError()
    {
        $this->error .= $this->read($this->pipes[2], strlen($this->error));
    }

    private function read($pipe, $offset = 0)
    {
        if (!is_resource($pipe)) {
            return '';
        }

        fseek($pipe, $offset);
        $read = '';
        do {
            $data = fread($pipe, 2 * 1024);
            $read .= $data;
        } while (!empty($data) && !isset($data[2 * 1024 - 1]));

        return $read;
    }

    public function __destruct()
    {
        $this->close();
    }
}

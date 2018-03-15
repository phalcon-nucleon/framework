<?php

namespace Neutrino\Process;

use Neutrino\Error\Error;

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

        /** @var Error $error */
        $error = null;

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$error) {
            $error = Error::fromError($errno, $errstr, $errfile, $errline);
        });

        $this->proc = proc_open($this->cmd, $this->spec, $this->pipes, $this->cwd, null, $this->options);

        restore_error_handler();

        $this->pipes = $this->spec;

        if (!is_null($error)) {
            throw new Exception('Can\'t create process', 0, new Exception($error->message, $error->code));
        }
        if (!$this->isRunning()) {
            throw new Exception('Can\'t create process');
        }

        return $this;
    }

    /**
     * Wait until process end or wait time
     *
     * @param int|null $wait in ms
     * @param int $step in ms
     */
    public function wait($wait = null, $step = 1000)
    {
        $withTimeout = false === is_null($wait);

        if ($withTimeout) {
            $start = microtime(true);
            if ($wait < $step) {
                $step = $wait;
            }
        }

        while ($this->isRunning()) {
            usleep($step * 1000);

            if ($withTimeout && ($start + $wait) > microtime(true)) {
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
     * @throws Exception
     */
    public function exec($timeout = null)
    {
        try {
            $this->start();
            $this->wait($timeout, 500);
            if ($this->isRunning()) {
                throw new Timeout;
            }
        } finally {
            $this->close();
        }
    }

    /**
     * Check if is running
     *
     * @return bool
     */
    public function isRunning()
    {
        return true === is_resource($this->proc) && true === $this->readStatus()['running'];
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
     * @return array|bool
     */
    public function readStatus()
    {
        if (is_resource($this->proc)) {
            $this->status = proc_get_status($this->proc);
        }

        return $this->status;
    }

    private function readOutput()
    {
        if (isset($this->pipes[1])) {
            $this->content .= $this->read($this->pipes[1], strlen($this->content));
        }
    }

    private function readError()
    {
        if (isset($this->pipes[2])) {
            $this->error .= $this->read($this->pipes[2], strlen($this->error));
        }
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

<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Attribute\Option;
use Neutrino\Cli\Task;
use Neutrino\Process\Process;
use RuntimeException;

final class ServerTask extends Task
{
    #[Description('Run the PHP built-in web server on public/app_dev.php.')]
    #[Option('--host={host}', 'Host or IP to listen on (default: 127.0.0.1).')]
    #[Option('--port={port}', 'Port to listen on (default: the first free one from 8000).')]
    public function mainAction(): void
    {
        try {
            $host = $this->getHost();
            $port = $this->getPort($host);
        } catch (RuntimeException $e) {
            $this->block([$e->getMessage()], 'error');

            return;
        }

        $this->run($host, $port);
    }

    private function getHost(): string
    {
        $host = $this->getOption('host', '127.0.0.1');

        if (!is_string($host) || $host === '') {
            throw new RuntimeException('Host can\'t be empty');
        }

        if (filter_var($host, FILTER_VALIDATE_IP) === false && filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw new RuntimeException('Host [' . $host . '] is not valid.');
        }

        return $host;
    }

    private function getPort(string $host): int
    {
        if (!$this->hasOption('port')) {
            return $this->acquirePort($host);
        }

        $port = $this->getOption('port');

        if (!is_scalar($port) || $port === '' || $port === true) {
            throw new RuntimeException('Port can\'t be empty');
        }

        $port = (int) $port;

        if ($this->portIsOpen($host, $port)) {
            throw new RuntimeException('Port [' . $port . '] on host [' . $host . '] is already used.');
        }

        return $port;
    }

    private function run(string $host, int $port): void
    {
        /** @var Process $process */
        $process = $this->getDI()->get(Process::class, [[PHP_BINARY, '-S', $host . ':' . $port, 'app_dev.php'], BASE_PATH . '/public']);
        $process->start();

        $this->block(['[OK] http://' . $host . ':' . $port], 'info');

        $process->watch(function (string $output, string $errorOutput): void {
            if ($output !== '') {
                $this->line(trim($output, "\n\r"));
            }
            if ($errorOutput !== '') {
                $this->error(trim($errorOutput, "\n\r"));
            }
        });

        $this->block(['[ERR] server suddenly stopped'], 'error');
    }

    private function acquirePort(string $host): int
    {
        $port = 8000;
        while ($this->portIsOpen($host, $port)) {
            $port++;
        }

        return $port;
    }

    private function portIsOpen(string $host, int $port): bool
    {
        $socket = @fsockopen($host, $port, $errno, $errstr, 0.1);

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }
}

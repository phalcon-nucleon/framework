<?php

declare(strict_types=1);

/*
 * A proxy for https requests (CONNECT tunnels), one connection at a time.
 *
 * php connect-proxy.php <port>: prints "ready" once listening.
 */

$server = stream_socket_server('tcp://127.0.0.1:' . $argv[1], $errno, $error);

if ($server === false) {
    fwrite(STDERR, $error);
    exit(1);
}

echo "ready\n";

while (true) {
    $client = @stream_socket_accept($server, 30);

    if ($client === false) {
        continue;
    }

    $request = (string) fgets($client);

    // Reads the head of the CONNECT request.
    do {
        $line = fgets($client);
    } while ($line !== false && rtrim($line) !== '');

    $target = preg_match('#^CONNECT (\S+) HTTP#', $request, $match) === 1 ? @stream_socket_client('tcp://' . $match[1], $errno, $error, 5) : false;

    if ($target === false) {
        fwrite($client, "HTTP/1.1 502 Bad Gateway\r\nContent-Length: 0\r\n\r\n");
        fclose($client);
        continue;
    }

    fwrite($client, "HTTP/1.1 200 Connection established\r\nProxy-Agent: test\r\n\r\n");

    // Relays the bytes both ways until one side closes.
    while (true) {
        $read = [$client, $target];
        $write = $except = null;

        if (stream_select($read, $write, $except, 5) < 1) {
            break;
        }

        foreach ($read as $from) {
            $data = fread($from, 65536);

            if ($data === '' || $data === false) {
                break 2;
            }

            fwrite($from === $client ? $target : $client, $data);
        }
    }

    fclose($target);
    fclose($client);
}

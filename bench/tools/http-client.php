<?php

/*
 * 100 GET requests to a local server (php -S), with the 1.3 or the 2.x HTTP client.
 *
 * php bench/tools/http-client.php <autoload> <curl|stream> [requests] [rounds]
 *
 * Prints the median time of a request, in µs, over the rounds. PHP 7.3 compatible (runs on 1.3).
 */

$autoload = $argv[1];
$transport = $argv[2];
$requests = isset($argv[3]) ? (int) $argv[3] : 100;
$rounds = isset($argv[4]) ? (int) $argv[4] : 7;

require $autoload;

$socket = stream_socket_server('tcp://127.0.0.1:0');
$port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
fclose($socket);

$router = dirname(__DIR__, 2) . '/tests/Test/HttpClient/fixtures/server.php';
$server = proc_open('exec ' . escapeshellarg(PHP_BINARY) . ' -S 127.0.0.1:' . $port . ' ' . escapeshellarg($router), [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
for ($i = 0; $i < 100 && !($probe = @fsockopen('127.0.0.1', $port)); $i++) {
    usleep(50000);
}
fclose($probe);

$url = 'http://127.0.0.1:' . $port . '/json';

if (class_exists('Neutrino\HttpClient\HttpClient')) {
    $client = new Neutrino\HttpClient\HttpClient([], $transport === 'curl' ? new Neutrino\HttpClient\Transport\CurlTransport() : new Neutrino\HttpClient\Transport\StreamTransport());
    $get = function () use ($client, $url) {
        return $client->request('GET', $url)->getContent();
    };
} else {
    $class = $transport === 'curl' ? 'Neutrino\HttpClient\Provider\Curl' : 'Neutrino\HttpClient\Provider\StreamContext';
    $get = function () use ($class, $url) {
        $request = new $class();

        return $request->get($url)->send()->getBody();
    };
}

for ($i = 0; $i < 10; $i++) {
    $get();
}

$times = [];
for ($round = 0; $round < $rounds; $round++) {
    $start = microtime(true);
    for ($i = 0; $i < $requests; $i++) {
        if ($get() !== '{"name":"nucleon","items":[1,2,3]}') {
            fwrite(STDERR, "Unexpected response\n");
            exit(1);
        }
    }
    $times[] = (microtime(true) - $start) / $requests * 1e6;
}

sort($times);
printf("%s %s: %.1f µs per request (median of %d rounds of %d)\n", PHP_VERSION, $transport, $times[intdiv(count($times), 2)], $rounds, $requests);

proc_terminate($server);

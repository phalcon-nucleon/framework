<?php

declare(strict_types=1);

/*
 * Router of the test server (php -S), see LocalServer.
 *
 * /echo                 the request as JSON: method, uri, query, headers (lower-case), body
 * /status/{code}        the status, body "status {code}"
 * /redirect/{code}?to=  a redirection to "to"
 * /slow?ms=             answers after ms milliseconds
 * /stream               5 chunks, 100 ms apart
 * /json, /bytes?size=   a JSON object, size bytes
 */

$path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$query = $_GET;

// A request through a proxy has an absolute URI.
if (preg_match('#^https?://#', $_SERVER['REQUEST_URI']) === 1) {
    $path = '/echo';
}

switch (true) {
    case $path === '/echo':
        $headers = [];
        foreach (getallheaders() as $name => $value) {
            $headers[strtolower($name)] = $value;
        }
        header('Content-Type: application/json');
        echo json_encode([
            'method'  => $_SERVER['REQUEST_METHOD'],
            'uri'     => $_SERVER['REQUEST_URI'],
            'query'   => $query,
            'headers' => $headers,
            'body'    => file_get_contents('php://input'),
        ]);
        break;

    case preg_match('#^/status/(\d{3})$#', $path, $match) === 1:
        http_response_code((int) $match[1]);
        echo 'status ' . $match[1];
        break;

    case preg_match('#^/redirect/(\d{3})$#', $path, $match) === 1:
        http_response_code((int) $match[1]);
        header('Location: ' . ($query['to'] ?? '/echo'));
        echo 'redirect';
        break;

    case $path === '/slow':
        usleep((int) ($query['ms'] ?? 1000) * 1000);
        echo 'slow';
        break;

    case $path === '/stream':
        header('Content-Type: text/plain');
        for ($i = 1; $i <= 5; $i++) {
            echo "chunk $i\n";
            flush();
            usleep(100000);
        }
        break;

    case $path === '/json':
        header('Content-Type: application/json');
        echo '{"name":"nucleon","items":[1,2,3]}';
        break;

    case $path === '/bytes':
        echo str_repeat('x', (int) ($query['size'] ?? 0));
        break;

    default:
        http_response_code(404);
        echo 'not found';
}

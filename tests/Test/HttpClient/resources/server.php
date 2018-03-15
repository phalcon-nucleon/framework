<?php

require __DIR__ . '/../../../../vendor/autoload.php';
use Neutrino\Http\Standards\Method;
use Neutrino\Http\Standards\StatusCode;
use Neutrino\HttpClient\Uri;

$uri = new Uri($_SERVER['REQUEST_URI']);

$parts = explode('/', trim($uri->path, '/'));

$httpCode    = !empty($parts[0]) ? $parts[0] : 200;
$httpMessage = !empty($parts[1]) ? $parts[1] : StatusCode::message($httpCode);
$method      = $_SERVER['REQUEST_METHOD'];
header("HTTP/1.1 $httpCode $httpMessage");
header("Status-Code: $httpCode $httpMessage");
header("Request-Method: {$method}");

$headers = [];
foreach ($_SERVER as $key => $value) {
    if (substr($key, 0, 5) <> 'HTTP_') {
        continue;
    }
    $header           = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
    $headers[$header] = $value;
}

$jsonRequest = isset($headers['Content-Type']) && $headers['Content-Type'] === 'application/json';

switch ($method) {
    case Method::GET:
    case Method::DELETE:
    case Method::HEAD:
        $query = $_GET;
        break;
    case Method::POST:
        if ($jsonRequest) {
            $query = json_decode(file_get_contents("php://input"), true);
        } else {
            $query = $_POST;
        }
        break;
    default:
        if ($jsonRequest) {
            $query = json_decode(file_get_contents("php://input"), true);
        } else {
            parse_str(urldecode(file_get_contents("php://input")), $query);
        }
}

if (!empty($query)) {
    if (isset($query['stream'])) {
        $output = implode('', range('1', '9')) . PHP_EOL;
        $loop   = 10000;

        header('Content-Length: ' . (strlen($output) * $loop));

        for ($i = 0; $i < $loop; $i++) {
            echo $output;
            ob_flush();
            flush();
        }
    }
}

ksort($headers);

echo json_encode(['header_send' => $headers, 'query' => $query]);

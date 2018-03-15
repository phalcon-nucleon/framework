<?php

namespace Test\HttpClient\Provider;

use Neutrino\Http\Standards\Method;
use Neutrino\Http\Standards\StatusCode;

/**
 * Class TraitCaller
 *
 * @package Test\HttpClient\Provider
 */
trait TraitWithLocalServer
{
    private static $server;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$server = proc_open(PHP_BINARY . ' -S  127.0.0.1:7999 server.php', [], $pipes, __DIR__ . '/../resources', null, ['bypass_shell' => true]);
    }

    public static function tearDownAfterClass()
    {
        proc_terminate(self::$server);
        proc_close(self::$server);

        parent::tearDownAfterClass();
    }

    public static function makeDataCall($method, $code, $status = null, $params = [], $json = false)
    {
        $statusMessage = is_null($status) ? StatusCode::message($code) : $status;
        $statusCode = $code . (empty($statusMessage) ? '' : ' ' . $statusMessage);

        $expected = [
            'code'    => $code,
            'status'  => $statusMessage,
            'body'    => '',
            'headers' => [
                'Status-Code'    => $statusCode,
                'Request-Method' => $method
            ]
        ];

        $header_send = [
            'Host'   => '127.0.0.1:7999',
        ];

        if (($method === Method::POST || $method === Method::PATCH || $method === Method::PUT) && !empty($params)) {
            $header_send['Content-Type']   = $json ? 'application/json' : 'application/x-www-form-urlencoded';
            $header_send['Content-Length'] = '' . strlen($json ? json_encode($params) : http_build_query($params));
        }

        if ($method !== Method::HEAD) {
            $expected['body'] = json_encode([
                'header_send' => $header_send,
                'query'       => $params
            ]);
        }

        return [$expected, $method, "/$code" . (!empty($status) ? "/" . trim($status) : ''), $params, $json];
    }
}

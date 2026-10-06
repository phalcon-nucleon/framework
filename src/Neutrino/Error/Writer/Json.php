<?php

declare(strict_types=1);

namespace Neutrino\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Phalcon\Di\Di;
use Phalcon\Http\ResponseInterface;

/**
 * Answers a fatal error with a JSON 500 response (Micro kernel). The error is detailed in debug mode only.
 */
final class Json implements Writable
{
    public function handle(Error $error): void
    {
        if (!$error->isFatal()) {
            return;
        }

        $content = ['code' => 500, 'status' => 'Internal Server Error'];

        if (APP_DEBUG) {
            $content['debug'] = $error;
        }

        $json = (string) json_encode($content, JSON_PARTIAL_OUTPUT_ON_ERROR);
        $di = Di::getDefault();
        $response = $di !== null && $di->has(Services::RESPONSE) ? $di->getShared(Services::RESPONSE) : null;

        if ($response instanceof ResponseInterface && !$response->isSent()) {
            $response
                ->setStatusCode(500, 'Internal Server Error')
                ->setContentType('application/json', 'UTF-8')
                ->setContent($json)
                ->send();

            return;
        }

        echo $json;
    }
}

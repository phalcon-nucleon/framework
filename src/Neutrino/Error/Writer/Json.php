<?php

namespace Neutrino\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Phalcon\Di;
use Phalcon\Http\Response;

/**
 * Class Json
 *
 * @package     Neutrino\Error\Writer
 */
class Json implements Writable
{

    public function handle(Error $error)
    {
        if (!$error->isFateful()) {
            return;
        }

        $di = Di::getDefault();

        if ($di
            && $di->has(Services::RESPONSE)
            && ($response = $di->getShared(Services::RESPONSE)) instanceof Response
            && !$response->isSent()
        ) {
            /** @var \Phalcon\Http\Response $response */
            $response
                ->setStatusCode(500)
                ->setJsonContent($error)
                ->send();
        } else {
            echo json_encode($error);
        }
    }
}

<?php

namespace Neutrino\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Support\Facades\Response;
use Phalcon\Di;

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
            && ($response = $di->getShared(Services::RESPONSE)) instanceof Response && !$response->isSent()
        ) {
            $response
                ->setStatusCode(500)
                ->setContent(json_encode($error))
                ->send();
        } else {
            echo json_encode($error);
        }
    }
}

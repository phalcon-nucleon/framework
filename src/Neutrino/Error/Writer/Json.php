<?php

namespace Neutrino\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Phalcon\Di;
use Phalcon\Http\ResponseInterface;

/**
 * Class Json
 *
 * @package     Neutrino\Error\Writer
 */
class Json implements Writable
{

    /**
     * @inheritdoc
     */
    public function handle(Error $error)
    {
        if (!$error->isFateful()) {
            return;
        }

        $return = [
            'code' => 500,
            'status' => 'Internal Server Error',
        ];

        if (APP_DEBUG) {
            $return['debug'] = $error;
        }

        $di = Di::getDefault();

        if ($di
            && $di->has(Services::RESPONSE)
            && ($response = $di->getShared(Services::RESPONSE)) instanceof ResponseInterface
            && !$response->isSent()
        ) {
            $response
                ->setStatusCode(500, 'Internal Server Error')
                ->setJsonContent($return)
                ->send();
        } else {
            echo json_encode($return);
        }
    }
}

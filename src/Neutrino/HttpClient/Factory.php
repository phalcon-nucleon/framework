<?php

namespace Neutrino\HttpClient;

use Neutrino\HttpClient\Provider\Curl;
use Neutrino\HttpClient\Provider\Exception as ProviderException;
use Neutrino\HttpClient\Provider\StreamContext;
use Phalcon\Di;

/**
 * Class Factory
 *
 * @package Neutrino\HttpClient
 */
final class Factory
{
    /**
     * @return \Neutrino\HttpClient\Request
     * @throws Exception
     */
    final static public function makeRequest()
    {
        try {
            Curl::checkAvailability();

            return Di::getDefault()->get(Curl::class);
        } catch (ProviderException $e) {
        }

        try {
            StreamContext::checkAvailability();

            return Di::getDefault()->get(StreamContext::class);
        } catch (ProviderException $e) {
        }

        throw new Exception('No provider available');
    }
}

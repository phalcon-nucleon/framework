<?php

namespace Neutrino\HttpClient;

use Neutrino\HttpClient\Provider\Curl;
use Neutrino\HttpClient\Provider\Exception as ProviderException;
use Neutrino\HttpClient\Provider\StreamContext;

/**
 * Class Factory
 *
 * @package Neutrino\HttpClient
 */
final class Factory
{
    /**
     * @return \Neutrino\HttpClient\Request
     */
    final static public function makeRequest()
    {
        try {
            Curl::checkAvailability();

            return new Curl();
        } catch (ProviderException $e) {
            try {
                StreamContext::checkAvailability();

                return new StreamContext();
            } catch (ProviderException $e) {
                return null;
            }
        }
    }
}

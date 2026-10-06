<?php

declare(strict_types=1);

namespace Neutrino\HttpClient;

use Generator;
use Neutrino\HttpClient\Exception\TransportException;

/**
 * Sends a request over the network: one exchange, the client follows the redirections.
 */
interface Transport
{
    /**
     * Yields the {@see Head} of the response, then the chunks of its body. Stopping the generator aborts the
     * exchange.
     *
     * @return Generator<int, Head|string>
     *
     * @throws TransportException
     */
    public function exchange(Request $request): Generator;
}

<?php

declare(strict_types=1);

namespace Neutrino\HttpClient\Exception;

use RuntimeException;

/**
 * The request could not be sent or the response could not be read: connection, TLS, timeout, protocol.
 */
final class TransportException extends RuntimeException implements HttpClientExceptionInterface {}

<?php

declare(strict_types=1);

namespace Neutrino\HttpClient\Exception;

use RuntimeException;

/**
 * The content of the response is not a JSON array or object.
 */
final class DecodingException extends RuntimeException implements HttpClientExceptionInterface {}

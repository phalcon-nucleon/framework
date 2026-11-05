<?php

declare(strict_types=1);

namespace Neutrino\HttpClient\Exception;

/**
 * An invalid option or URL.
 */
final class InvalidArgumentException extends \InvalidArgumentException implements HttpClientExceptionInterface {}

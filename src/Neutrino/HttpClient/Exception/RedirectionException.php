<?php

declare(strict_types=1);

namespace Neutrino\HttpClient\Exception;

/**
 * A response with a 3xx status: a redirection not followed, or `max_redirects` reached.
 */
final class RedirectionException extends HttpException {}

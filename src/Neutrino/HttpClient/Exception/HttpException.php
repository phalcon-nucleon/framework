<?php

declare(strict_types=1);

namespace Neutrino\HttpClient\Exception;

use Neutrino\HttpClient\ResponseInterface;
use RuntimeException;

/**
 * A response with a 3xx, 4xx or 5xx status.
 */
abstract class HttpException extends RuntimeException implements HttpClientExceptionInterface
{
    final public function __construct(private readonly ResponseInterface $response)
    {
        $url = $response->getInfo('url');
        $code = $response->getStatusCode();

        parent::__construct('HTTP ' . $code . ' returned for "' . (is_string($url) ? $url : '') . '".', $code);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}

<?php

declare(strict_types=1);

namespace Neutrino\Http\Standards;

/**
 * HTTP request methods.
 *
 * Typed constants rather than an enum: they are compared with `Request::getMethod()`
 * and passed to the router as strings.
 */
final class Method
{
    public const string GET     = 'GET';
    public const string POST    = 'POST';
    public const string PUT     = 'PUT';
    public const string PATCH   = 'PATCH';
    public const string DELETE  = 'DELETE';
    public const string HEAD    = 'HEAD';
    public const string OPTIONS = 'OPTIONS';
    public const string TRACE   = 'TRACE';
    public const string CONNECT = 'CONNECT';
}

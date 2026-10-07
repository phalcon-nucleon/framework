<?php

declare(strict_types=1);

namespace Neutrino\Http\Middleware;

use Neutrino\Constants\Services;
use Neutrino\Foundation\Middleware\Controller;
use Neutrino\Http\Standards\StatusCode;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Phalcon\Config\Config;
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Encryption\Security;
use Phalcon\Events\Event;
use Phalcon\Http\Request;
use RuntimeException;

/**
 * Cross Site Request Forgery protection: the requests that change something (POST, PUT, PATCH, DELETE) must
 * carry the CSRF token of the session (`security->getToken()`), in the `X-CSRF-Token` header or in the
 * `_csrf_token` field of the body. GET, HEAD and OPTIONS are not checked: a token in the URL leaks in the logs
 * and the `Referer`.
 *
 * Render the token with {@see Csrf::token()}, not `security->getToken()`: Phalcon creates a new token at the
 * first `getToken()` of each request, which invalidates the forms already open in other tabs. `Csrf::token()`
 * returns the token of the session, created once: it stays valid for the session, for several tabs and
 * successive AJAX calls. `security.csrf.rotate = true` renews it after each valid request.
 */
class Csrf extends Controller implements BeforeInterface
{
    public const string FIELD = '_csrf_token';

    public const string HEADER = 'X-CSRF-Token';

    private const array SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function before(Event $event, object $source, mixed $data = null)
    {
        $di = $this->getDI();
        /** @var Request $request */
        $request = $di->getShared(Services::REQUEST);

        // The real method: Phalcon's getMethod() follows the X-HTTP-Method-Override header, and `_method` when
        // the application allows it, so that a POST could pass for a GET.
        $method = $request->getServer('REQUEST_METHOD');

        if (in_array(is_string($method) ? strtoupper($method) : '', self::SAFE_METHODS, true)) {
            return true;
        }

        $token = self::requestToken($request);
        /** @var Security $security */
        $security = $di->getShared(Services::SECURITY);
        /** @var Config $config */
        $config = $di->getShared(Services::CONFIG);

        if ($token !== '' && $security->checkToken(self::FIELD, $token, (bool) $config->path('security.csrf.rotate', false))) {
            return true;
        }

        /** @var \Phalcon\Http\Response $response */
        $response = $di->getShared(Services::RESPONSE);
        $response->setStatusCode(StatusCode::FORBIDDEN, (string) StatusCode::message(StatusCode::FORBIDDEN));

        return false;
    }

    /**
     * The CSRF token of the session, created on the first call.
     */
    public static function token(?DiInterface $container = null): string
    {
        $container ??= Di::getDefault() ?? throw new RuntimeException('Csrf::token() needs a container.');
        /** @var Security $security */
        $security = $container->getShared(Services::SECURITY);
        $token = $security->getSessionToken();

        return is_string($token) && $token !== '' ? $token : (string) $security->getToken();
    }

    private static function requestToken(Request $request): string
    {
        $token = $request->getHeader(self::HEADER);

        if ($token !== '') {
            return $token;
        }

        $token = match ($request->getMethod()) {
            'POST'  => $request->getPost(self::FIELD),
            'PATCH' => $request->getPatch(self::FIELD) ?? $request->getPost(self::FIELD),
            default => $request->getPut(self::FIELD) ?? $request->getPost(self::FIELD),
        };

        if ($token === null && str_contains(strtolower($request->getContentType() ?? ''), 'json')) {
            $body = $request->getJsonRawBody(true);
            $token = is_array($body) ? $body[self::FIELD] ?? null : null;
        }

        return is_string($token) ? $token : '';
    }
}

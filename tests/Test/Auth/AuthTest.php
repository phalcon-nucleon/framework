<?php

declare(strict_types=1);

namespace Test\Auth;

use Neutrino\Config\Config;
use Neutrino\Constants\Services;
use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\Providers;
use Neutrino\Support\Facades\Auth;
use Neutrino\Support\Facades\Facade;
use Phalcon\Auth\Manager;
use Phalcon\Db\Adapter\Pdo\Sqlite;
use Phalcon\Di\Di;
use Phalcon\Di\FactoryDefault;
use Phalcon\Http\Response\Cookies;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Test\TestCase\ArraySession;
use Test\Auth\Stub\User;

final class AuthTest extends TestCase
{
    private const string PASSWORD = '1a2b3c4d5e';

    private FactoryDefault $di;

    private ArraySession $session;

    private Sqlite $db;

    protected function setUp(): void
    {
        $this->db = new Sqlite(['dbname' => ':memory:']);
        $this->db->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, email VARCHAR(100), password VARCHAR(255), remember_token VARCHAR(100))');

        $this->session = new ArraySession();
        $this->di = $this->container(['auth' => ['model' => User::class], 'session' => ['id' => 'auth_user']]);

        $security = $this->di->getShared(Services::SECURITY);
        $this->db->insert('users', [1, 'test@email.com', $security->hash(self::PASSWORD), null], ['id', 'email', 'password', 'remember_token']);
        $this->db->insert('users', [2, 'other@email.com', $security->hash('other'), null], ['id', 'email', 'password', 'remember_token']);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Di::reset();
        $_COOKIE = [];
        unset($_SERVER['HTTP_USER_AGENT']);
    }

    public function testServiceIsLazyAndShared(): void
    {
        $this->assertFalse($this->di->getService(Services::AUTH)->isResolved());
        $this->assertFalse($this->di->getService(Services::SESSION)->isResolved());

        $auth = $this->di->getShared(Services::AUTH);

        $this->assertInstanceOf(Manager::class, $auth);
        $this->assertSame($auth, $this->di->getShared(Manager::class));
    }

    public function testAttempt(): void
    {
        $this->assertTrue(Auth::guest());

        $this->assertTrue(Auth::attempt(['email' => 'test@email.com', 'password' => self::PASSWORD]));

        $this->assertTrue(Auth::check());
        $this->assertFalse(Auth::guest());
        $this->assertSame(1, $this->session->regenerated, 'The session id is regenerated (session fixation).');
        $this->assertSame('test@email.com', $this->session->data['auth_user'], 'The 1.3 session key and identifier.');

        $user = Auth::user();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('test@email.com', $user->getAuthIdentifier());
        $this->assertSame('test@email.com', Auth::id());
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function wrongCredentials(): iterable
    {
        yield 'wrong password' => [['email' => 'test@email.com', 'password' => 'wrong']];
        yield 'unknown user' => [['email' => 'nobody@email.com', 'password' => self::PASSWORD]];
        yield 'no password' => [['email' => 'test@email.com']];
    }

    /**
     * @param array<string, string> $credentials
     */
    #[DataProvider('wrongCredentials')]
    public function testFailedAttempt(array $credentials): void
    {
        $this->assertFalse(Auth::attempt($credentials));
        $this->assertFalse(Auth::check());
        $this->assertSame([], $this->session->data);
    }

    public function testUserFromTheSession(): void
    {
        $this->session->data['auth_user'] = 'other@email.com';

        $this->assertSame(2, Auth::user()?->id);
    }

    public function testLoginAndLoginUsingId(): void
    {
        Auth::login(User::findFirst(2));
        $this->assertSame('other@email.com', Auth::id());

        Auth::logout();
        $this->assertTrue(Auth::guest());

        $user = Auth::loginUsingId(1);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('test@email.com', Auth::id());
        $this->assertNull(Auth::loginUsingId(99));

        Auth::logout();
        $this->assertSame('other@email.com', Auth::loginUsingId('2')?->getAuthIdentifier(), 'A string primary key.');
        Auth::logout();
        $this->assertNull(Auth::loginUsingId('0 OR id = 2'), 'Bound, not read as PHQL conditions.');
        $this->assertTrue(Auth::guest());
    }

    public function testLogout(): void
    {
        Auth::attempt(['email' => 'test@email.com', 'password' => self::PASSWORD]);

        Auth::logout();

        $this->assertTrue(Auth::guest());
        $this->assertArrayNotHasKey('auth_user', $this->session->data);
    }

    public function testRememberMe(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Browser/1.0';

        Auth::attempt(['email' => 'test@email.com', 'password' => self::PASSWORD], true);

        $cookie = $this->cookies()->get('remember_me');
        $payload = json_decode((string) $cookie->getValue(), true);
        $this->assertIsArray($payload);
        $this->assertSame('test@email.com', $payload['id']);
        $this->assertTrue($cookie->getHttpOnly());
        $this->assertLessThanOrEqual(time() + 365 * 86400, $cookie->getExpiration(), 'One year at most (1.3: 100 years).');

        // The token is stored hashed, never in clear.
        $stored = $this->storedToken(1);
        $this->assertNotNull($stored);
        $this->assertNotSame($payload['token'], $stored);
        $this->assertStringNotContainsString($payload['token'], $stored);

        // Next request: no session, the remember-me cookie.
        $this->newRequest(['remember_me' => (string) $cookie->getValue()]);

        $this->assertSame('test@email.com', Auth::id());
        $this->assertTrue(Auth::guard()->viaRemember());
        $this->assertSame('test@email.com', $this->session->data['auth_user']);
    }

    public function testRememberTokenIsBoundToTheUserAgent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Browser/1.0';
        Auth::attempt(['email' => 'test@email.com', 'password' => self::PASSWORD], true);
        $value = (string) $this->cookies()->get('remember_me')->getValue();

        $_SERVER['HTTP_USER_AGENT'] = 'Stolen/1.0';
        $this->newRequest(['remember_me' => $value]);

        $this->assertTrue(Auth::guest());
    }

    public function testWrongRememberToken(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Browser/1.0';
        Auth::attempt(['email' => 'test@email.com', 'password' => self::PASSWORD], true);

        $this->newRequest(['remember_me' => json_encode(['id' => 'test@email.com', 'token' => str_repeat('0', 60), 'user_agent' => 'Browser/1.0'])]);

        $this->assertTrue(Auth::guest());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedCookies(): iterable
    {
        yield '1.3 format' => ['test@email.com|token'];
        yield 'not json' => ['{'];
        yield 'json scalar' => ['12'];
        yield 'no id' => ['{"token":"x"}'];
    }

    #[DataProvider('malformedCookies')]
    public function testMalformedRememberCookie(string $value): void
    {
        $this->newRequest(['remember_me' => $value]);

        $this->assertTrue(Auth::guest());
    }

    public function testLogoutRevokesTheRememberToken(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Browser/1.0';
        Auth::attempt(['email' => 'test@email.com', 'password' => self::PASSWORD], true);
        $value = (string) $this->cookies()->get('remember_me')->getValue();

        // Logout from a request authenticated by the cookie.
        $this->newRequest(['remember_me' => $value]);
        $this->assertTrue(Auth::check());
        Auth::logout();

        $this->assertNull($this->storedToken(1));

        // The stolen cookie no longer works.
        $this->newRequest(['remember_me' => $value]);
        $this->assertTrue(Auth::guest());
    }

    public function testGuardsConfig(): void
    {
        $this->di = $this->container(['auth' => ['guards' => ['web' => [
            'type'    => 'session',
            'default' => true,
            'adapter' => ['name' => 'model', 'options' => ['model' => User::class, 'idColumn' => 'id']],
            'options' => ['name' => 'session_key', 'rememberTtl' => 60],
        ]]]]);

        Auth::attempt(['email' => 'test@email.com', 'password' => self::PASSWORD]);

        $this->assertSame('test@email.com', $this->session->data['session_key'], 'The identifier is the one of the model.');
        $this->assertSame('test@email.com', Auth::id());
    }

    public function testIdColumnIsTheIdentifierOfTheModel(): void
    {
        $this->di = $this->container(['auth' => ['guards' => ['web' => [
            'type'    => 'session',
            'default' => true,
            'adapter' => ['name' => 'model', 'options' => ['model' => User::class]],
        ]]]]);

        $this->session->data = [];
        Auth::attempt(['email' => 'other@email.com', 'password' => 'other']);
        $this->newRequest([], keepSession: true);

        $this->assertSame(2, Auth::user()?->id);
    }

    public function testNoConfig(): void
    {
        $this->di = $this->container([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Auth: no guard, set "auth.guards".');

        Auth::check();
    }

    /**
     * @param array<string, mixed> $config
     */
    private function container(array $config): FactoryDefault
    {
        Facade::clearResolvedInstances();

        $di = new FactoryDefault();
        Di::setDefault($di);
        $di->setShared(Services::CONFIG, new Config($config));
        $di->setShared(Services::DB, $this->db);
        $di->setShared(Services::SESSION, $this->session);

        ProviderRegistrar::register($di, [Providers\Security::class, Providers\Auth::class]);
        $di->getShared(Services::SECURITY)->setWorkFactor(4);

        $cookies = new Cookies(false);
        $cookies->setDI($di);
        $di->setShared(Services::COOKIES, $cookies);

        Facade::setDependencyInjection($di);

        return $di;
    }

    /**
     * A new request: a new container, the cookies of the browser, the session kept or emptied.
     *
     * @param array<string, string> $cookies
     */
    private function newRequest(array $cookies, bool $keepSession = false): void
    {
        $config = $this->di->getShared(Services::CONFIG)->toArray();

        if (!$keepSession) {
            $this->session = new ArraySession();
        }

        $_COOKIE = $cookies;
        $this->di = $this->container($config);
    }

    private function cookies(): Cookies
    {
        /** @var Cookies */
        return $this->di->getShared(Services::COOKIES);
    }

    private function storedToken(int $id): ?string
    {
        $row = $this->db->fetchOne('SELECT remember_token FROM users WHERE id = ' . $id);

        return is_array($row) && is_string($row['remember_token']) ? $row['remember_token'] : null;
    }
}

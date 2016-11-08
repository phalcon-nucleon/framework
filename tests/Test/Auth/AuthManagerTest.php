<?php
/**
 * Created by PhpStorm.
 * User: xlzi590
 * Date: 18/10/2016
 * Time: 10:45
 */

namespace Test\Auth;


use Luxury\Auth\AuthManager;
use Luxury\Constants\Services;
use Luxury\Foundation\Auth\User;
use Luxury\Support\Facades\Auth;
use Luxury\Support\Facades\Session;
use Luxury\Support\Str;
use Phalcon\Db\Column;
use Phalcon\Http\Response\Cookies;
use Phalcon\Security;
use Test\TestCase\TestCase;

class AuthManagerTest extends TestCase
{
    public function setUp()
    {
        global $config;

        $config['session']['id'] = 'unittest';
        $config['auth']['model'] = User::class;

        parent::setUp();
    }

    public function mockDb($numRows, $fetchall)
    {
        $con = $this->mockService(Services::DB, \Phalcon\Db\Adapter\Pdo\Mysql::class, true);

        $dialect = $this->createMock(\Phalcon\Db\Dialect\Mysql::class);

        $results = $this->createMock(\Phalcon\Db\Result\Pdo::class);

        $results->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue($numRows));

        $results->expects($this->any())
            ->method('fetchall')
            ->will($this->returnValue($fetchall));

        $dialect->expects($this->any())
            ->method('select')
            ->will($this->returnValue(null));

        $con->expects($this->any())
            ->method('getDialect')
            ->will($this->returnValue($dialect));

        $con->expects($this->any())
            ->method('query')
            ->will($this->returnValue($results));

        $con->expects($this->any())
            ->method('execute');

        $con->expects($this->any())
            ->method('tableExists')
            ->will($this->returnValue(true));

        $con->expects($this->any())
            ->method('describeColumns')
            ->will($this->returnValue([
                new Column('id', [
                    "type"          => Column::TYPE_INTEGER,
                    "size"          => 10,
                    "unsigned"      => true,
                    "notNull"       => true,
                    "autoIncrement" => true,
                    "first"         => true
                ]),
                new Column('name', [
                    "type"    => Column::TYPE_VARCHAR,
                    "size"    => 64,
                    "notNull" => true
                ]),
                new Column('my_user_name', [
                    "type"    => Column::TYPE_VARCHAR,
                    "size"    => 64,
                    "notNull" => true
                ]),
                new Column('email', [
                    "type"    => Column::TYPE_VARCHAR,
                    "size"    => 64,
                    "notNull" => true
                ]),
                new Column('password', [
                    "type"    => Column::TYPE_VARCHAR,
                    "size"    => 32,
                    "notNull" => true
                ]),
                new Column('my_user_password', [
                    "type"    => Column::TYPE_VARCHAR,
                    "size"    => 32,
                    "notNull" => true
                ]),
                new Column('remember_token', [
                    "type"    => Column::TYPE_VARCHAR,
                    "size"    => 60,
                    "notNull" => true
                ]),
            ]));
    }

    public function testNoAttemps()
    {
        $this->mockDb(0, null);
        /** @var AuthManager $authManager */
        $authManager = new AuthManager();

        $this->assertNull($authManager->attempt([
            'email'    => '',
            'password' => ''
        ]));

        $this->assertFalse($authManager->check());
        $this->assertTrue($authManager->guest());
    }

    public function testAttemps()
    {
        $security = $this->getDI()->getShared(Services::SECURITY);

        $this->mockDb(1, [
            [
                'id'       => 1,
                'email'    => 'test@email.com',
                'password' => $security->hash('1a2b3c4d5e')
            ]
        ]);
        /** @var AuthManager $authManager */
        $authManager = new AuthManager();
        $this->getDI()->setShared(Services::AUTH, $authManager);

        $sessionService = $this->mockService(Services::SESSION, \Phalcon\Session\Adapter\Files::class, true);

        $sessionService->expects($this->once())->method('regenerateId')->willReturn(1);
        $sessionService->expects($this->once())->method('set')->with('unittest', 'test@email.com');

        /** @var User $user */
        $user = Auth::attempt([
            'email'    => 'test@email.com',
            'password' => '1a2b3c4d5e'
        ]);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($user, Auth::user());

        $this->assertTrue(Auth::check());
        $this->assertFalse(Auth::guest());
        $this->assertEquals('test@email.com', $user->getAuthIdentifier());
        $this->assertTrue($security->checkHash('1a2b3c4d5e', $user->getAuthPassword()));
    }

    public function testAttempsFail()
    {
        $security = $this->getDI()->getShared(Services::SECURITY);

        $this->mockDb(1, [
            [
                'id'       => 1,
                'email'    => 'test@email.com',
                'password' => $security->hash('1a2b3c4d5e')
            ]
        ]);
        /** @var AuthManager $authManager */
        $authManager = new AuthManager();
        $this->getDI()->setShared(Services::AUTH, $authManager);

        /** @var User $user */
        $user = Auth::attempt([
            'email'    => 'test@email.com',
            'password' => '1a2b3c4ddadaaddad5e'
        ]);
        $this->assertNull($user);
        $this->assertNull(Auth::user());

        $this->assertFalse(Auth::check());
        $this->assertTrue(Auth::guest());
    }

    public function testAttempsCustomModel()
    {
        $this->getDI()->getShared(Services::CONFIG)->auth->model = CustomUser::class;

        $security = $this->getDI()->getShared(Services::SECURITY);

        $this->mockDb(1, [
            [
                'id'               => 1,
                'my_user_name'     => 'test@email.com',
                'my_user_password' => $security->hash('1a2b3c4d5e')
            ]
        ]);
        /** @var AuthManager $authManager */
        $authManager = new AuthManager();
        $this->getDI()->setShared(Services::AUTH, $authManager);

        $sessionService = $this->mockService(Services::SESSION, \Phalcon\Session\Adapter\Files::class, true);

        $sessionService->expects($this->once())->method('regenerateId')->willReturn(1);
        $sessionService->expects($this->once())->method('set')->with('unittest', 'test@email.com');

        /** @var CustomUser $user */
        $user = Auth::attempt([
            'my_user_name'     => '',
            'my_user_password' => '1a2b3c4d5e'
        ]);
        $this->assertInstanceOf(CustomUser::class, $user);
        $this->assertEquals($user, Auth::user());

        $this->assertTrue(Auth::check());
        $this->assertFalse(Auth::guest());
        $this->assertEquals('test@email.com', $user->getAuthIdentifier());
        $this->assertTrue($security->checkHash('1a2b3c4d5e', $user->getAuthPassword()));
    }

    public function testAttempsWithRemember()
    {
        $security = $this->getDI()->getShared(Services::SECURITY);

        $this->mockDb(1, [
            [
                'id'       => 1,
                'email'    => 'test@email.com',
                'password' => $security->hash('1a2b3c4d5e')
            ]
        ]);
        /** @var AuthManager $authManager */
        $authManager = new AuthManager();
        $this->getDI()->setShared(Services::AUTH, $authManager);

        $sessionService = $this->mockService(Services::SESSION, \Phalcon\Session\Adapter\Files::class, true);

        $sessionService->expects($this->once())->method('regenerateId')->willReturn(1);
        $sessionService->expects($this->once())->method('set')->with('unittest', 'test@email.com');

        /** @var User $user */
        $user = Auth::attempt([
            'email'    => 'test@email.com',
            'password' => '1a2b3c4d5e'
        ], true);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($user, Auth::user());

        $this->assertTrue(Auth::check());
        $this->assertFalse(Auth::guest());
        $this->assertEquals('test@email.com', $user->getAuthIdentifier());
        $this->assertTrue($security->checkHash('1a2b3c4d5e', $user->getAuthPassword()));

        /** @var Cookies $cookies */
        $cookies = $this->getDI()->getShared(Services::COOKIES);
        $this->assertTrue($cookies->has('remember_me'));

        $cookieValue = $cookies->get('remember_me')->getValue();
        $this->assertCount(2, explode('|', $cookieValue));
        $this->assertEquals('test@email.com', explode('|', $cookieValue)[0]);
    }

    public function testAttempsViaRemember()
    {
        /** @var Cookies $cookies */
        $cookies = $this->getDI()->getShared(Services::COOKIES);
        /** @var Security $security */
        $security = $this->getDI()->getShared(Services::SECURITY);
        $token    = Str::random(60);

        $this->mockDb(1, [
            [
                'id'             => 1,
                'email'          => 'test@email.com',
                'password'       => $security->hash('1a2b3c4d5e'),
                'remember_token' => $token
            ]
        ]);
        $cookies->set('remember_me', 'test@email.com|' . $token);

        /** @var AuthManager $authManager */
        $authManager = new AuthManager();
        $this->getDI()->setShared(Services::AUTH, $authManager);

        $sessionService = $this->mockService(Services::SESSION, \Phalcon\Session\Adapter\Files::class, true);

        $sessionService->expects($this->once())->method('get')->with('unittest')->willReturn(null);
        $sessionService->expects($this->once())->method('set');

        /** @var User $user */
        $user = Auth::user();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($user, Auth::user());

        $this->assertTrue(Auth::check());
        $this->assertFalse(Auth::guest());
        $this->assertEquals('test@email.com', $user->getAuthIdentifier());
        $this->assertTrue($security->checkHash('1a2b3c4d5e', $user->getAuthPassword()));
    }

    public function testAttempsViaSession()
    {
        /** @var Security $security */
        $security = $this->getDI()->getShared(Services::SECURITY);
        $this->mockDb(1, [
            [
                'id'       => 1,
                'email'    => 'test@email.com',
                'password' => $security->hash('1a2b3c4d5e')
            ]
        ]);

        /** @var AuthManager $authManager */
        $authManager = new AuthManager();
        $this->getDI()->setShared(Services::AUTH, $authManager);

        $sessionService = $this->mockService(Services::SESSION, \Phalcon\Session\Adapter\Files::class, true);

        $sessionService->expects($this->once())->method('get')->with('unittest')->willReturn('test@email.com');

        /** @var User $user */
        $user = Auth::user();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($user, Auth::user());

        $this->assertTrue(Auth::check());
        $this->assertFalse(Auth::guest());
        $this->assertEquals('test@email.com', $user->getAuthIdentifier());
        $this->assertTrue($security->checkHash('1a2b3c4d5e', $user->getAuthPassword()));
    }

    public function testLogout()
    {

        /** @var AuthManager $authManager */
        $authManager = new AuthManager();
        $this->getDI()->setShared(Services::AUTH, $authManager);

        $sessionService = $this->mockService(Services::SESSION, \Phalcon\Session\Adapter\Files::class, true);

        $sessionService->expects($this->once())->method('destroy');

        Auth::logout();

        $this->assertNull(Auth::user());
        $this->assertFalse(Auth::check());
        $this->assertTrue(Auth::guest());
    }
}

class CustomUser extends User
{
    public static function getAuthIdentifierName()
    {
        return 'my_user_name';
    }

    public static function getAuthPasswordName()
    {
        return 'my_user_password';
    }

    public static function getRememberTokenName()
    {
        return 'my_user_remember';
    }
}
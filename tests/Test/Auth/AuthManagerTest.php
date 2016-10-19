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
use Phalcon\Db\Column;
use Test\TestCase\TestCase;

class AuthManagerTest extends TestCase
{
    public function setUp(){
        global $config;

        $config['session']['id'] = 'unittest';
        $config['auth']['model'] = User::class;

        parent::setUp();
    }

    public function mockDb($numRows, $fetchall){

        $con = $this->getMockBuilder(\Phalcon\Db\Adapter\Pdo\Mysql::class)
            ->disableOriginalConstructor()
            //->setMockClassName(\Phalcon\Db\Adapter\Pdo\Mysql::class)
            ->setMethods(array('getDialect', 'query', 'execute', 'tableExists', 'describeColumns'))
            ->getMock();

        $dialect = $this->getMockBuilder(\Phalcon\Db\Dialect\Mysql::class)
            ->disableOriginalConstructor()
            //->setMockClassName(\Phalcon\Db\Dialect\Mysql::class)
            ->setMethods(array('select'))
            ->getMock();
        //$dialect = $this->getMock('\\Phalcon\\Db\\Dialect\\Mysql', array('select'), array(), '', false);

        $results = $this->getMockBuilder(\Phalcon\Db\Result\Pdo::class)
            ->disableOriginalConstructor()
            //->setMockClassName(\Phalcon\Db\Result\Pdo::class)
            ->setMethods(array('numRows', 'setFetchMode', 'fetchall'))
            ->getMock();
        //$results = $this->getMock('\\Phalcon\\Db\\Result\\Pdo', array('numRows', 'setFetchMode', 'fetchall'), array(), '', false);

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
                    "type" => Column::TYPE_INTEGER,
                    "size" => 10,
                    "unsigned" => true,
                    "notNull" => true,
                    "autoIncrement" => true,
                    "first" => true
                ]),
                new Column('name', [
                    "type" => Column::TYPE_VARCHAR,
                    "size" => 64,
                    "notNull" => true
                ]),
                new Column('my_user_name', [
                    "type" => Column::TYPE_VARCHAR,
                    "size" => 64,
                    "notNull" => true
                ]),
                new Column('email', [
                    "type" => Column::TYPE_VARCHAR,
                    "size" => 64,
                    "notNull" => true
                ]),
                new Column('password', [
                    "type" => Column::TYPE_VARCHAR,
                    "size" => 32,
                    "notNull" => true
                ]),
                new Column('my_user_password', [
                    "type" => Column::TYPE_VARCHAR,
                    "size" => 32,
                    "notNull" => true
                ]),
            ]));

        $this->getDI()->set('db', $con);
    }

    public function testNoAttemps()
    {
        $this->mockDb(0, null);
        /** @var AuthManager $authManager */
        $authManager = new AuthManager();

        $this->assertNull($authManager->attempt([
            'email' => '', 'password' => ''
        ]));

        $this->assertFalse($authManager->check());
        $this->assertTrue($authManager->guest());
    }

    public function testAttemps()
    {
        $this->mockDb(1, [['id' => 1, 'email' => 'test@email.com', 'password' => '1a2b3c4d5e']]);
        /** @var AuthManager $authManager */
        $authManager = new AuthManager();
        $this->getDI()->setShared(Services::AUTH, $authManager);

        Session::shouldReceive('regenerateId')->once()->andReturn(1);
        Session::shouldReceive('set')->once()->with('unittest', 1);

        /** @var User $user */
        $user = Auth::attempt([
            'email' => '', 'password' => ''
        ]);
        $this->assertInstanceOf(User::class, $user);

        $this->assertTrue($authManager->check());
        $this->assertFalse($authManager->guest());
        $this->assertEquals('test@email.com', $user->getAuthIdentifier());
        $this->assertEquals('1a2b3c4d5e', $user->getAuthPassword());
    }

    public function testAttempsCustomModel()
    {
        $this->getDI()->getShared(Services::CONFIG)->auth->model = CustomUser::class;

        $this->mockDb(1, [['id' => 1, 'my_user_name' => 'test@email.com', 'my_user_password' => '1a2b3c4d5e']]);
        /** @var AuthManager $authManager */
        $authManager = new AuthManager();
        $this->getDI()->setShared(Services::AUTH, $authManager);

        Session::shouldReceive('regenerateId')->once()->andReturn(1);
        Session::shouldReceive('set')->once()->with('unittest', 1);

        /** @var CustomUser $user */
        $user = Auth::attempt([
            'my_user_name' => '', 'my_user_password' => ''
        ]);
        $this->assertInstanceOf(CustomUser::class, $user);

        $this->assertTrue($authManager->check());
        $this->assertFalse($authManager->guest());
        $this->assertEquals('test@email.com', $user->getAuthIdentifier());
        $this->assertEquals('1a2b3c4d5e', $user->getAuthPassword());
    }
}

class CustomUser extends User {
    public static function getAuthIdentifierName() : string
    {
        return 'my_user_name';
    }

    public static function getAuthPasswordName() : string
    {
        return 'my_user_password';
    }

    public static function getRememberTokenName() : string
    {
        return 'my_user_remember';
    }
}
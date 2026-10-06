<?php

declare(strict_types=1);

namespace Test\Auth;

use Neutrino\Foundation\Auth\User;
use Neutrino\Model\Attribute\Column;
use Neutrino\Model\Attribute\Primary;
use Phalcon\Db\Column as Type;
use Test\Models\DatabaseTestCase;

/**
 * `Foundation\Auth\User` on `Neutrino\Model`: described without introspection, remember-me token hashed.
 */
final class FoundationUserTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db()->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, email VARCHAR(100) NOT NULL, password VARCHAR(255) NOT NULL, remember_token VARCHAR(100))');
        $this->db()->insert('users', [1, 'ada@example.com', 'hash'], ['id', 'email', 'password']);
        $_SERVER['HTTP_USER_AGENT'] = 'Browser/1.0';
        $this->queries = [];
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);

        parent::tearDown();
    }

    public function testUser(): void
    {
        $user = AppUser::findFirst(['[email] = :e:', 'bind' => ['e' => 'ada@example.com']]);

        $this->assertInstanceOf(AppUser::class, $user);
        $this->assertSame('ada@example.com', $user->getAuthIdentifier());
        $this->assertSame('hash', $user->getAuthPassword());

        $token = $user->createRememberToken('clear-token');
        $stored = (string) $this->db()->fetchColumn('SELECT remember_token FROM users WHERE id = 1');
        $this->assertSame(64, strlen($stored));
        $this->assertStringNotContainsString('clear-token', $stored);
        $this->assertSame('Browser/1.0', $token->getUserAgent());

        $fresh = AppUser::findFirst(1);
        $this->assertNotNull($fresh?->getRememberToken('clear-token'));
        $this->assertNull($fresh->getRememberToken('wrong'));

        $this->assertTrue($token->delete());
        // fetchColumn() returns false for NULL on Phalcon 6.
        $row = $this->db()->fetchOne('SELECT remember_token FROM users WHERE id = 1');
        $this->assertIsArray($row);
        $this->assertArrayHasKey('remember_token', $row);
        $this->assertNull($row['remember_token']);
        $this->assertSame([], $this->introspectionQueries());
    }
}

final class AppUser extends User
{
    #[Primary]
    public ?int $id = null;

    #[Column(Type::TYPE_VARCHAR)]
    public ?string $email = null;

    #[Column(Type::TYPE_VARCHAR)]
    public ?string $password = null;

    #[Column(Type::TYPE_VARCHAR, nullable: true)]
    public ?string $remember_token = null;

    public function initialize()
    {
        parent::initialize();
        $this->setSource('users');
    }
}

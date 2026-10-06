<?php

declare(strict_types=1);

namespace Test\Database\Migrations;

use InvalidArgumentException;
use Neutrino\Database\Migrations\Migration;
use Neutrino\Database\Migrations\MigrationCreator;
use Neutrino\Database\Migrations\Migrator;
use Neutrino\Database\Migrations\Prefix\TimestampPrefix;
use Neutrino\Database\Migrations\Storage\StorageInterface;
use Neutrino\Database\Schema\Builder;
use Phalcon\Db\Adapter\Pdo\Sqlite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Test\TestCase\TestCase as AppTestCase;

final class MigrationCreatorTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = AppTestCase::dataDirectory() . 'migrations-' . uniqid();
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->path . '/*') ?: []);
        @rmdir($this->path);
    }

    /**
     * @return iterable<string, array{string|null, bool, string, string|null}>
     */
    public static function stubs(): iterable
    {
        yield 'blank' => [null, false, '//', null];
        yield 'create' => ['users', true, "\$schema->create('users'", "\$schema->dropIfExists('users');"];
        yield 'update' => ['users', false, "\$schema->table('users'", null];
    }

    #[DataProvider('stubs')]
    public function testCreate(?string $table, bool $create, string $up, ?string $down): void
    {
        $file = (new MigrationCreator(new TimestampPrefix()))->create('my_migration', $this->path, $table, $create);

        $this->assertMatchesRegularExpression('#/\d{10,}_my_migration\.php$#', $file);
        $content = (string) file_get_contents($file);
        $this->assertStringContainsString('return new class extends Migration {', $content);
        $this->assertStringContainsString($up, $content);
        if ($down !== null) {
            $this->assertStringContainsString($down, $content);
        }

        // The file is a migration.
        $migration = (new Migrator($this->createStub(StorageInterface::class), new TimestampPrefix()))->resolve($file);
        $this->assertInstanceOf(Migration::class, $migration);

        $schema = new Builder(new Sqlite(['dbname' => ':memory:']));
        if ($table !== null && !$create) {
            $schema->execute('CREATE TABLE users (id INTEGER)');
        }
        $migration->up($schema);
        $migration->down($schema);
        $this->assertSame($table !== null && !$create, $schema->hasTable('users'));
    }

    public function testAlreadyExists(): void
    {
        $creator = new MigrationCreator(new TimestampPrefix());
        $creator->create('create_users_table', $this->path);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^A migration "create_users_table" already exists: \d+_create_users_table\.php\.$/');

        $creator->create('create_users_table', $this->path);
    }

    public function testInvalidName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid migration name "../evil": letters, digits and underscores only.');

        (new MigrationCreator(new TimestampPrefix()))->create('../evil', $this->path);
    }

    public function testStubsPath(): void
    {
        $this->assertFileExists((new MigrationCreator(new TimestampPrefix()))->stubsPath() . '/blank.stub');
    }
}

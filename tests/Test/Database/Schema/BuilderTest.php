<?php

declare(strict_types=1);

namespace Test\Database\Schema;

use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Builder;
use Neutrino\Database\Schema\Exception\CommandException;
use Neutrino\Database\Schema\Grammar;
use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Db\Column;
use Phalcon\Db\IndexInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Test\Database\Connections;
use Throwable;

/**
 * Schema changes run on SQLite, MySQL and PostgreSQL.
 */
final class BuilderTest extends TestCase
{
    use Connections;

    protected function tearDown(): void
    {
        $this->closeConnections();
        Builder::$defaultStringLength = 255;
    }

    #[DataProvider('connections')]
    public function testCreateEveryType(string $database): void
    {
        $schema = $this->schema($database);

        $schema->create('users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('email')->unique();
        });
        $schema->create('everything', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->boolean('flag')->default(false);
            $table->tinyInteger('tiny')->default(1);
            $table->smallInteger('small')->nullable();
            $table->mediumInteger('medium')->nullable();
            $table->integer('count')->default(0);
            $table->unsignedBigInteger('big')->nullable();
            $table->decimal('price', 2, 8)->default(9.99);
            $table->double('ratio')->nullable();
            $table->float('weight')->nullable();
            $table->json('data')->nullable();
            $table->jsonb('datab')->nullable();
            $table->char('code', 2)->nullable();
            $table->string('name', 20)->default("it's");
            $table->text('body')->nullable();
            $table->mediumText('medium_body')->nullable();
            $table->longText('long_body')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->blob('blob')->nullable();
            $table->tinyBlob('tiny_blob')->nullable();
            $table->mediumBlob('medium_blob')->nullable();
            $table->longBlob('long_blob')->nullable();
            $table->date('day')->nullable();
            $table->dateTime('at')->nullable();
            $table->dateTimeTz('at_tz', 3)->nullable();
            $table->time('hour')->nullable();
            $table->timeTz('hour_tz')->nullable();
            $table->timestamp('stamp', 6)->nullable();
            $table->timestampTz('stamp_tz')->nullable();
            $table->uuid('uuid')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->macAddress('mac')->nullable();
            $table->timestamps(3);
            $table->softDeletes();
            $table->rememberToken();
            $table->nullableMorphs('owner');
        });

        $this->assertTrue($schema->hasTable('everything'));
        $this->assertFalse($schema->hasTable('nothing'));
        $this->assertCount(38, $schema->getColumnListing('everything'));
        $this->assertTrue($schema->hasColumns('everything', ['ID', 'flag', 'owner_type', 'remember_token']));
        $this->assertFalse($schema->hasColumns('everything', ['id', 'missing']));
        $this->assertTrue($schema->hasColumn('everything', 'deleted_at'));
        $this->assertSame(Column::TYPE_VARCHAR, $schema->getColumnType('everything', 'name'));
        $this->assertNull($schema->getColumnType('everything', 'missing'));

        $db = $schema->connection();
        $db->insert('everything', ['x'], ['code']);
        $row = $db->fetchOne('SELECT * FROM everything');

        $this->assertIsArray($row);
        $this->assertEquals(1, $row['id']);
        $this->assertEquals(0, $row['flag']);
        $this->assertEquals(1, $row['tiny']);
        $this->assertEquals(0, $row['count']);
        $this->assertEquals(9.99, $row['price']);
        $this->assertSame("it's", $row['name']);
        $this->assertSame('draft', $row['status']);
        $this->assertNotEmpty($row['created_at'], 'CURRENT_TIMESTAMP by default');
        $this->assertNull($row['deleted_at']);
    }

    #[DataProvider('connections')]
    public function testEnumIsChecked(string $database): void
    {
        $schema = $this->schema($database);
        $schema->create('posts', fn(Blueprint $table) => $table->enum('status', ['draft', 'published']));

        $this->assertThrows(fn() => $schema->connection()->insert('posts', ['deleted'], ['status']));
    }

    #[DataProvider('connections')]
    public function testIndexes(string $database): void
    {
        $schema = $this->schema($database);
        $schema->create('users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('first');
            $table->string('last')->index('by_last');
            $table->index(['first', 'last']);
        });

        $this->assertSame(['by_last', 'users_email_unique', 'users_first_last_index'], $this->indexes($schema->connection(), 'users'));

        $db = $schema->connection();
        $db->insert('users', ['a@b.c', 'A', 'B'], ['email', 'first', 'last']);
        $this->assertThrows(fn() => $db->insert('users', ['a@b.c', 'C', 'D'], ['email', 'first', 'last']));

        $schema->table('users', function (Blueprint $table): void {
            $table->dropUnique('users_email_unique');
            $table->dropIndex(['by_last', 'users_first_last_index']);
        });

        $this->assertSame([], $this->indexes($db, 'users'));
    }

    #[DataProvider('connections')]
    public function testForeignKeys(string $database): void
    {
        $schema = $this->schema($database);
        $schema->enableForeignKeyConstraints();
        $schema->create('users', fn(Blueprint $table) => $table->increments('id'));
        $schema->create('posts', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedInteger('editor_id')->nullable()->foreign()->references('id')->on('users');
        });

        $db = $schema->connection();
        $references = array_keys($db->describeReferences('posts'));
        if ($database !== 'sqlite') {
            // SQLite does not name its foreign keys.
            $this->assertEqualsCanonicalizing(['posts_editor_id_foreign_users_id', 'posts_user_id_foreign_users_id'], $references);
        }
        $this->assertCount(2, $references);

        $db->insert('users', [1], ['id']);
        $db->insert('posts', [1], ['user_id']);
        $this->assertThrows(fn() => $db->insert('posts', [2], ['user_id']));

        $db->delete('users', 'id = 1');
        $this->assertEquals(0, $db->fetchColumn('SELECT COUNT(*) FROM posts'), 'ON DELETE CASCADE');

        if ($database === 'sqlite') {
            $this->expectException(CommandException::class);
            $this->expectExceptionMessage('Schema command "dropForeign" on "posts": Dropping a foreign key constraint is not supported by SQLite');
        }

        $schema->table('posts', fn(Blueprint $table) => $table->dropForeign('posts_user_id_foreign_users_id'));
        $db->insert('posts', [2], ['user_id']);

        $this->assertCount(1, $db->describeReferences('posts'));
    }

    #[DataProvider('connections')]
    public function testUpdate(string $database): void
    {
        $schema = $this->schema($database);
        $schema->create('users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('nick')->nullable();
            $table->string('old')->nullable();
        });
        $schema->connection()->insert('users', ['Ada'], ['name']);

        $schema->table('users', function (Blueprint $table): void {
            $table->dropColumn('old');
            $table->renameColumn('nick', 'alias');
            $table->string('email')->nullable()->unique();
            $table->integer('score')->default(10);
        });

        $this->assertSame(['id', 'name', 'alias', 'email', 'score'], $this->columns($schema, 'users'));
        $this->assertEquals(10, $schema->connection()->fetchColumn('SELECT score FROM users'));
        $this->assertContains('users_email_unique', $this->indexes($schema->connection(), 'users'));

        if ($database === 'sqlite') {
            $this->expectException(CommandException::class);
            $this->expectExceptionMessage('Schema command "modifyColumn" on "users": Altering a DB column is not supported by SQLite');
        }

        // An existing column is modified.
        $schema->table('users', fn(Blueprint $table) => $table->string('name', 100)->nullable());

        $name = array_values(array_filter($schema->getColumnListing('users'), static fn(Column $column): bool => $column->getName() === 'name'))[0];
        $this->assertEquals(100, $name->getSize());
        $this->assertFalse($name->isNotNull());
    }

    #[DataProvider('connections')]
    public function testAddAnAutoIncrementColumn(string $database): void
    {
        $schema = $this->schema($database);
        $schema->create('items', fn(Blueprint $table) => $table->string('name'));
        $db = $schema->connection();
        $db->insert('items', ['a'], ['name']);

        if ($database === 'sqlite') {
            $this->expectException(CommandException::class);
            $this->expectExceptionMessage('Schema command "addColumn" on "items": ');
        }

        $schema->table('items', fn(Blueprint $table) => $table->increments('id'));

        $db->insert('items', ['b'], ['name']);
        $this->assertEquals([1, 2], array_map(intval(...), array_column($db->fetchAll('SELECT id FROM items ORDER BY id'), 'id')));
        $this->assertTrue($this->column($schema, 'items', 'id')->isPrimary());

        // An existing primary key is kept: the column is only modified.
        $schema->table('items', fn(Blueprint $table) => $table->bigIncrements('id'));

        $this->assertSame(Column::TYPE_BIGINTEGER, $schema->getColumnType('items', 'id'));
        $db->insert('items', ['c'], ['name']);
        $this->assertEquals(3, $db->fetchColumn("SELECT id FROM items WHERE name = 'c'"));
    }

    #[DataProvider('connections')]
    public function testRenameWithOtherChanges(string $database): void
    {
        $schema = $this->schema($database);
        $schema->create('users', fn(Blueprint $table) => $table->increments('id'));

        $schema->table('users', function (Blueprint $table): void {
            $table->rename('members');
            $table->string('nick')->nullable();
        });

        $this->assertFalse($schema->hasTable('users'));
        $this->assertTrue($schema->hasColumn('members', 'nick'));
    }

    #[DataProvider('connections')]
    public function testPrimaryKeys(string $database): void
    {
        $schema = $this->schema($database);
        $schema->create('pivot', function (Blueprint $table): void {
            $table->integer('user_id');
            $table->integer('role_id');
            $table->primary(['user_id', 'role_id']);
        });

        $db = $schema->connection();
        $db->insert('pivot', [1, 1], ['user_id', 'role_id']);
        $db->insert('pivot', [1, 2], ['user_id', 'role_id']);
        $this->assertThrows(fn() => $db->insert('pivot', [1, 1], ['user_id', 'role_id']));

        if ($database === 'sqlite') {
            $this->expectException(CommandException::class);
            $this->expectExceptionMessage('SQLite cannot drop the primary key of an existing table.');
        }

        $schema->table('pivot', fn(Blueprint $table) => $table->dropPrimary());
        $db->insert('pivot', [1, 1], ['user_id', 'role_id']);

        $db->delete('pivot');
        $schema->table('pivot', fn(Blueprint $table) => $table->primary('role_id'));
        $db->insert('pivot', [1, 1], ['user_id', 'role_id']);
        $this->assertThrows(fn() => $db->insert('pivot', [2, 1], ['user_id', 'role_id']));
    }

    #[DataProvider('connections')]
    public function testRenameAndDrop(string $database): void
    {
        $schema = $this->schema($database);
        $schema->create('a', fn(Blueprint $table) => $table->increments('id'));

        $schema->rename('a', 'b');
        $this->assertFalse($schema->hasTable('a'));
        $this->assertTrue($schema->hasTable('b'));

        $schema->drop('b');
        $this->assertFalse($schema->hasTable('b'));

        $schema->dropIfExists('b');

        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('Schema command "drop" on "b"');

        $schema->drop('b');
    }

    #[DataProvider('connections')]
    public function testDropAllTables(string $database): void
    {
        $schema = $this->schema($database);
        $schema->create('users', fn(Blueprint $table) => $table->increments('id'));
        $schema->create('posts', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id')->foreign()->references('id')->on('users');
        });

        $schema->dropAllTables();
        $schema->dropAllTables();

        $this->assertSame([], array_values(array_filter($schema->connection()->listTables(), static fn(string $table): bool => !str_starts_with($table, 'sqlite_'))));
    }

    #[DataProvider('connections')]
    public function testRawSql(string $database): void
    {
        $schema = $this->schema($database);
        $schema->execute('CREATE TABLE raw_table (id INTEGER)');
        $schema->table('raw_table', fn(Blueprint $table) => $table->sql('INSERT INTO raw_table (id) VALUES (7)'));

        $this->assertEquals(7, $schema->connection()->fetchColumn('SELECT id FROM raw_table'));
    }

    #[DataProvider('connections')]
    public function testTemporaryTable(string $database): void
    {
        $schema = $this->schema($database);
        $schema->create('temp', function (Blueprint $table): void {
            $table->temporary();
            $table->integer('id');
        });

        $schema->connection()->insert('temp', [1], ['id']);

        $this->assertEquals(1, $schema->connection()->fetchColumn('SELECT COUNT(*) FROM temp'));
    }

    public function testMysqlTableOptions(): void
    {
        $schema = $this->schema('mysql');
        $schema->create('options', function (Blueprint $table): void {
            $table->option('engine', 'MyISAM');
            $table->integer('id');
        });

        $this->assertSame('MyISAM', $schema->connection()->fetchColumn("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'options'"));
    }

    public function testMysqlOnUpdate(): void
    {
        $schema = $this->schema('mysql');
        $schema->create('stamped', function (Blueprint $table): void {
            $table->integer('id');
            $table->timestamps(6);
        });

        $db = $schema->connection();
        $db->insert('stamped', [1, '2020-01-01 00:00:00'], ['id', 'updated_at']);
        $db->execute('UPDATE stamped SET id = 2');

        $this->assertNotSame('2020-01-01 00:00:00.000000', $db->fetchColumn('SELECT updated_at FROM stamped'));
        $this->assertMatchesRegularExpression('/\.\d{6}$/', (string) $db->fetchColumn('SELECT created_at FROM stamped'));
    }

    public function testDefaultConnectionAndGrammar(): void
    {
        $db = $this->open('sqlite');
        $di = new \Phalcon\Di\Di();
        $di->setShared('db', $db);
        \Phalcon\Di\Di::setDefault($di);

        try {
            $schema = new Builder();

            $this->assertSame($db, $schema->connection());
            $this->assertInstanceOf(Grammar\Sqlite::class, $schema->grammar());
        } finally {
            \Phalcon\Di\Di::reset();
        }
    }

    public function testUnknownDialect(): void
    {
        $db = $this->createMock(AdapterInterface::class);
        $db->method('getDialectType')->willReturn('oracle');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No schema grammar for the "oracle" dialect');

        new Builder($db);
    }

    public function testBlueprintResolverAndDefaultStringLength(): void
    {
        $schema = $this->schema('sqlite');
        $resolved = [];
        $schema->blueprintResolver(function (string $table) use (&$resolved): Blueprint {
            $resolved[] = $table;

            return new Blueprint($table);
        });

        Builder::defaultStringLength(191);
        $schema->create('users', fn(Blueprint $table) => $table->string('name'));

        $this->assertSame(['users'], $resolved);
        $this->assertSame(191, $schema->getColumnListing('users')[0]->getSize());
    }

    private function schema(string $database): Builder
    {
        return new Builder($this->open($database));
    }

    private function column(Builder $schema, string $table, string $name): Column
    {
        foreach ($schema->getColumnListing($table) as $column) {
            if ($column->getName() === $name && $column instanceof Column) {
                return $column;
            }
        }

        $this->fail("No column $name.");
    }

    /**
     * @return list<string>
     */
    private function columns(Builder $schema, string $table): array
    {
        return array_map(static fn(Column $column): string => $column->getName(), $schema->getColumnListing($table));
    }

    /**
     * Indexes other than the primary key, sorted.
     *
     * @return list<string>
     */
    private function indexes(AdapterInterface $db, string $table): array
    {
        $names = array_keys(array_filter(
            $db->describeIndexes($table),
            static fn(IndexInterface $index, string $name): bool => $index->getType() !== 'PRIMARY' && $name !== 'PRIMARY' && !str_ends_with($name, '_pkey') && !str_starts_with($name, 'sqlite_'),
            ARRAY_FILTER_USE_BOTH,
        ));
        sort($names);

        return $names;
    }

    private function assertThrows(\Closure $callback): void
    {
        try {
            $callback();
        } catch (Throwable) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('No exception thrown.');
    }
}

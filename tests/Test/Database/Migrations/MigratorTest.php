<?php

declare(strict_types=1);

namespace Test\Database\Migrations;

use Neutrino\Cli\Output\Decorate;
use Neutrino\Constants\Events;
use Neutrino\Constants\Services;
use Neutrino\Database\Migrations\Migration;
use Neutrino\Database\Migrations\MigrationInterface;
use Phalcon\Db\Adapter\AbstractAdapter;
use Phalcon\Events\Manager;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

final class MigratorTest extends MigrationsTestCase
{
    private const array SCHEMA = ['1700000001_create_users_table', '1700000002_create_posts_table', '1700000003_add_published_to_posts'];

    protected function setUp(): void
    {
        Decorate::setColorSupport(false);
    }

    protected function tearDown(): void
    {
        Decorate::setColorSupport(null);
        unset($GLOBALS['listeners']);

        parent::tearDown();
    }

    #[DataProvider('connections')]
    public function testRun(string $database): void
    {
        $this->boot($database);
        $this->storage()->createStorage();

        $files = $this->migrator()->run(self::MIGRATIONS . '/schema');

        $this->assertSame(array_map(static fn(string $name): string => self::MIGRATIONS . "/schema/$name.php", self::SCHEMA), $files);
        $this->assertSame(['migrations', 'posts', 'users'], $this->tables());
        $this->assertTrue($this->db()->tableExists('posts'));
        $this->assertSame(self::SCHEMA, $this->storage()->getRan());
        $this->assertSame(1, $this->storage()->getLastBatchNumber());
        $this->assertSame([
            'Migrating: 1700000001_create_users_table',
            'Migrated:  1700000001_create_users_table',
            'Migrating: 1700000002_create_posts_table',
            'Migrated:  1700000002_create_posts_table',
            'Migrating: 1700000003_add_published_to_posts',
            'Migrated:  1700000003_add_published_to_posts',
        ], $this->migrator()->getNotes());

        $this->assertSame([], $this->migrator()->run(self::MIGRATIONS . '/schema'));
        $this->assertSame(['Nothing to migrate.'], $this->migrator()->getNotes());
    }

    #[DataProvider('connections')]
    public function testRollbackTheLastBatch(string $database): void
    {
        $this->boot($database);
        $this->storage()->createStorage();
        $path = self::MIGRATIONS . '/schema';

        $this->migrator()->runPending([$path . '/1700000001_create_users_table.php']);
        $this->migrator()->runPending([$path . '/1700000002_create_posts_table.php', $path . '/1700000003_add_published_to_posts.php']);

        // The whole last batch (Nucleon 1.3 rolled back one migration of it).
        $rolledBack = $this->migrator()->rollback($path);

        $this->assertSame([$path . '/1700000003_add_published_to_posts.php', $path . '/1700000002_create_posts_table.php'], $rolledBack);
        $this->assertSame(['migrations', 'users'], $this->tables());
        $this->assertSame(['1700000001_create_users_table'], $this->storage()->getRan());
        $this->assertSame([
            'Rolling back: 1700000003_add_published_to_posts',
            'Rolled back:  1700000003_add_published_to_posts',
            'Rolling back: 1700000002_create_posts_table',
            'Rolled back:  1700000002_create_posts_table',
        ], $this->migrator()->getNotes());

        $this->migrator()->rollback($path);
        $this->assertSame([], $this->storage()->getRan());

        $this->assertSame([], $this->migrator()->rollback($path));
        $this->assertSame(['Nothing to rollback.'], $this->migrator()->getNotes());
    }

    public function testStep(): void
    {
        $this->boot();
        $this->storage()->createStorage();
        $path = self::MIGRATIONS . '/schema';

        $this->migrator()->run($path, ['step' => true]);

        $this->assertSame(3, $this->storage()->getLastBatchNumber());

        $this->migrator()->rollback($path, ['step' => 2]);

        $this->assertSame(['1700000001_create_users_table'], $this->storage()->getRan());
        $this->assertSame(['migrations', 'users'], $this->tables());
    }

    #[DataProvider('connections')]
    public function testReset(string $database): void
    {
        $this->boot($database);
        $this->storage()->createStorage();
        $path = self::MIGRATIONS . '/schema';

        $this->migrator()->run($path, ['step' => true]);
        $this->migrator()->reset($path);

        $this->assertSame([], $this->storage()->getRan());
        $this->assertSame(['migrations'], $this->tables());

        $this->assertSame([], $this->migrator()->reset($path));
        $this->assertSame(['Nothing to rollback.'], $this->migrator()->getNotes());
    }

    public function testLegacyMigrations(): void
    {
        $this->boot();
        $this->storage()->createStorage();
        $paths = [self::MIGRATIONS . '/migrations_dir_1', self::MIGRATIONS . '/migrations_dir_2', self::MIGRATIONS];

        $this->migrator()->run($paths);

        $this->assertSame(['1511272551_CreateOne', '1511272584_CreateTwo', '1511272593_UpdateOne', '1511272605_CreateTree', '1511272613_UpdateTwo', '1511272620_UpdateTree', '1511367165_DownOne'], $this->storage()->getRan());
        $this->assertSame(1, $GLOBALS['listeners']['CreateOne::up']);
        $this->assertSame(1, $GLOBALS['listeners']['DownOne::up']);

        $this->migrator()->reset($paths);

        $this->assertSame(1, $GLOBALS['listeners']['CreateOne::down']);
        $this->assertSame(1, $GLOBALS['listeners']['UpdateTree::down']);
        $this->assertSame([], $this->storage()->getRan());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function transactions(): iterable
    {
        yield 'sqlite' => ['sqlite', true];
        yield 'postgresql' => ['postgresql', true];
        // MySQL commits each DDL statement.
        yield 'mysql' => ['mysql', false];
    }

    #[DataProvider('transactions')]
    public function testFailingMigrationIsRolledBack(string $database, bool $rolledBack): void
    {
        $this->boot($database);
        $this->storage()->createStorage();

        try {
            $this->migrator()->run(self::MIGRATIONS . '/failing');
            $this->fail('No exception.');
        } catch (RuntimeException $e) {
            $this->assertSame('Broken migration.', $e->getMessage());
        }

        $this->assertSame(['1700000010_create_logs_table'], $this->storage()->getRan());
        $this->assertSame($rolledBack ? ['logs', 'migrations'] : ['broken', 'logs', 'migrations'], $this->tables());
    }

    public function testForeignKeysDisabledInASqliteMigration(): void
    {
        $this->boot();
        $this->storage()->createStorage();
        $this->db()->execute('PRAGMA foreign_keys = ON');

        // SQLite ignores `foreign_keys` in a transaction: the checks are deferred to the commit.
        $this->migrator()->run(self::MIGRATIONS . '/rebuild');

        $this->assertSame(['1700000040_create_tables', '1700000041_rebuild_users'], $this->storage()->getRan());
        $this->assertEquals(1, $this->db()->fetchColumn('SELECT COUNT(*) FROM posts JOIN users ON users.id = posts.user_id'));
        $this->assertEquals(1, $this->db()->fetchColumn('PRAGMA foreign_keys'));
        $this->assertEquals(0, $this->db()->fetchColumn('PRAGMA defer_foreign_keys'));

        $this->expectException(\PDOException::class);
        $this->db()->execute('INSERT INTO posts (user_id) VALUES (2)');
    }

    public function testMigrationWithoutTransaction(): void
    {
        $this->boot();
        $this->storage()->createStorage();

        try {
            $this->migrator()->run(self::MIGRATIONS . '/failing-without-transaction');
            $this->fail('No exception.');
        } catch (RuntimeException) {
        }

        $this->assertSame(['broken', 'migrations'], $this->tables());
        $this->assertSame([], $this->storage()->getRan());
    }

    public function testMigrationOnItsConnection(): void
    {
        $this->boot();
        $this->storage()->createStorage();

        $this->migrator()->run(self::MIGRATIONS . '/reports');

        $this->assertSame(['reports'], $this->tables(Services::DB . '.reports'));
        $this->assertSame(['migrations'], $this->tables(), 'The log stays on the default connection.');
        $this->assertSame(['1700000020_create_reports_table'], $this->storage()->getRan());

        $this->migrator()->rollback(self::MIGRATIONS . '/reports');
        $this->assertSame([], $this->tables(Services::DB . '.reports'));
    }

    public function testDefaultConnectionOfTheMigrations(): void
    {
        $this->boot();
        $this->storage()->createStorage();

        $this->migrator()->setConnection('reports');
        $this->migrator()->run(self::MIGRATIONS . '/schema');

        $this->assertSame(['posts', 'users'], $this->tables(Services::DB . '.reports'));
        $this->assertSame(['migrations'], $this->tables());
    }

    #[DataProvider('connections')]
    public function testPretend(string $database): void
    {
        $this->boot($database);
        $this->storage()->createStorage();
        $path = self::MIGRATIONS . '/schema';

        // The statements that ran.
        $ran = [];
        $manager = new Manager();
        $manager->attach(Events\Db::AFTER_QUERY, function ($event, AbstractAdapter $db) use (&$ran): void {
            $ran[] = $db->getSQLStatement();
        });
        $db = $this->db();
        $this->assertInstanceOf(AbstractAdapter::class, $db);
        $db->setEventsManager($manager);

        $this->migrator()->run($path, ['pretend' => true]);

        $notes = $this->migrator()->getNotes();
        $this->assertSame('1700000001_create_users_table:', $notes[0]);
        $this->assertStringContainsString('CREATE TABLE', $notes[1]);
        $this->assertStringContainsString('users', $notes[1]);
        $this->assertContains('1700000003_add_published_to_posts:', $notes);
        $this->assertNotEmpty(preg_grep('/ALTER TABLE .posts. ADD/', $notes), 'The table is read: the column is added.');
        $this->assertSame([], preg_grep('/^\s*(CREATE|ALTER|INSERT|DROP)/i', $ran), 'Nothing written.');
        $this->assertSame(['migrations'], $this->tables());
        $this->assertSame([], $this->storage()->getRan());

        $this->migrator()->run($path);
        $this->migrator()->rollback($path, ['pretend' => true]);

        $this->assertContains('1700000001_create_users_table:', $this->migrator()->getNotes());
        $this->assertNotEmpty(preg_grep('/DROP TABLE IF EXISTS .users./', $this->migrator()->getNotes()));
        $this->assertCount(3, $this->storage()->getRan());
    }

    public function testPretendIsHighlighted(): void
    {
        Decorate::setColorSupport(true);
        $this->boot();
        $this->storage()->createStorage();

        $this->migrator()->run(self::MIGRATIONS . '/schema', ['pretend' => true]);

        $this->assertStringContainsString("\e[", $this->migrator()->getNotes()[1]);
    }

    public function testResolve(): void
    {
        $this->boot();
        $migrator = $this->migrator();
        $anonymous = self::MIGRATIONS . '/schema/1700000002_create_posts_table.php';
        $named = self::MIGRATIONS . '/schema/1700000001_create_users_table.php';

        $this->assertInstanceOf(Migration::class, $migrator->resolve($anonymous));
        $this->assertSame($migrator->resolve($anonymous), $migrator->resolve($anonymous));
        $this->assertInstanceOf(\CreateUsersTable::class, $migrator->resolve($named));
        $this->assertInstanceOf(MigrationInterface::class, $migrator->resolve($named));

        $migrator->requireFiles([$named]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('/invalid/1700000030_nothing.php: return an instance of ' . Migration::class);

        $migrator->resolve(self::MIGRATIONS . '/invalid/1700000030_nothing.php');
    }

    public function testMigrationFilesAndPaths(): void
    {
        $this->boot();
        $migrator = $this->migrator();

        $files = $migrator->getMigrationFiles([self::MIGRATIONS . '/migrations_dir_2', self::MIGRATIONS . '/migrations_dir_1/']);
        $this->assertSame(['1511272551_CreateOne', '1511272584_CreateTwo', '1511272593_UpdateOne', '1511272605_CreateTree', '1511272613_UpdateTwo', '1511272620_UpdateTree'], array_keys($files));
        $this->assertSame([], $migrator->getMigrationFiles('/nowhere'));

        $migrator->path('/a');
        $migrator->path('/a');
        $migrator->path('/b');
        $this->assertSame(['/a', '/b'], $migrator->paths());
        $this->assertSame($this->storage(), $migrator->getStorage());
        $this->assertFalse($migrator->storageExist());
    }

    public function testRollbackOfAMissingFile(): void
    {
        $this->boot();
        $this->storage()->createStorage();
        $this->storage()->log('1600000000_gone', 1);

        $this->assertSame([], $this->migrator()->rollback(self::MIGRATIONS . '/schema'));
        $this->assertSame(['Migration not found: 1600000000_gone'], $this->migrator()->getNotes());
    }
}

<?php

declare(strict_types=1);

namespace Test\Database\Cli;

use Fake\Kernels\Cli\StubKernelCliMigrations;
use Neutrino\Constants\Services;
use Phalcon\Db\Adapter\AdapterInterface;
use Test\Cli\CliTestCase;

/**
 * The migration commands, run through a console kernel declaring the migration provider, on SQLite in memory.
 */
final class MigrationTasksTest extends CliTestCase
{
    private const string MIGRATIONS = BASE_PATH . '/migrations';

    private const string MAKE_PATH = 'migrations/tmp';

    protected static function kernelClassInstance(): string
    {
        return StubKernelCliMigrations::class;
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::setConfig([
            'database'   => ['default' => 'main', 'connections' => [
                'main'    => ['adapter' => 'sqlite', 'config' => ['dbname' => ':memory:']],
                'reports' => ['adapter' => 'sqlite', 'config' => ['dbname' => ':memory:']],
            ]],
            'migrations' => ['path' => self::MIGRATIONS . '/schema'],
        ]);
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob(BASE_PATH . '/' . self::MAKE_PATH . '/*') ?: []);
        @rmdir(BASE_PATH . '/' . self::MAKE_PATH);

        parent::tearDown();
    }

    public function testList(): void
    {
        $output = $this->runCommand('list');

        $this->assertMatchesRegularExpression('/^ migrate +Run the database migrations\.\s*$/m', $output);
        $this->assertMatchesRegularExpression('/^ migrate:rollback +Rollback the last batch of migrations\.\s*$/m', $output);
        $this->assertStringContainsString('make:migration', $output);
    }

    public function testHelp(): void
    {
        $output = $this->runCommand('help migrate');

        $this->assertStringContainsString('--database={name}', $output);
        $this->assertStringContainsString('--pretend', $output);
    }

    public function testMigrate(): void
    {
        $output = $this->runCommand('migrate');

        $this->assertStringContainsString('Migration table created successfully.', $output);
        $this->assertStringContainsString('Migrated:  1700000001_create_users_table', $output);
        $this->assertStringContainsString('Migrated:  1700000003_add_published_to_posts', $output);
        $this->assertSame(['migrations', 'posts', 'users'], $this->tables());

        $this->output->out = '';
        $this->assertStringContainsString('Nothing to migrate.', $this->runCommand('migrate'));
    }

    public function testMigrateByStep(): void
    {
        $this->runCommand('migrate --step');
        $this->runCommand('migrate:rollback');

        $this->assertSame(['migrations', 'posts', 'users'], $this->tables());
        $this->assertSame(2, $this->ran());
    }

    public function testPretend(): void
    {
        $output = $this->runCommand('migrate --pretend');

        $this->assertStringContainsString('1700000001_create_users_table:', $output);
        $this->assertMatchesRegularExpression('/CREATE TABLE "users"/', $output);
        $this->assertSame(['migrations'], $this->tables());
        $this->assertSame(0, $this->ran());
    }

    public function testStatus(): void
    {
        $this->assertStringContainsString('Migration table not found.', $this->runCommand('migrate:status'));

        $this->runCommand('migrate --step --path=migrations/schema');
        $this->runCommand('migrate:rollback');
        $this->output->out = '';

        $output = $this->runCommand('migrate:status');

        $this->assertSame(
            <<<'TXT'
            +------+-----------------------------------+
            | RAN? | MIGRATION                         |
            +------+-----------------------------------+
            | Y    | 1700000001_create_users_table     |
            | Y    | 1700000002_create_posts_table     |
            | N    | 1700000003_add_published_to_posts |
            +------+-----------------------------------+

            TXT,
            $output,
        );

        $this->output->out = '';
        $this->assertStringContainsString('No migrations found.', $this->runCommand('migrate:status --path=migrations/none'));
    }

    public function testInstall(): void
    {
        $this->assertStringContainsString('Migration table created successfully.', $this->runCommand('migrate:install'));
        $this->assertStringContainsString('Migration table already exists.', $this->runCommand('migrate:install'));
    }

    public function testRollbackSteps(): void
    {
        $this->runCommand('migrate --step');

        $output = $this->runCommand('migrate:rollback --step=2');

        $this->assertStringContainsString('Rolled back:  1700000003_add_published_to_posts', $output);
        $this->assertStringContainsString('Rolled back:  1700000002_create_posts_table', $output);
        $this->assertSame(['migrations', 'users'], $this->tables());
    }

    public function testReset(): void
    {
        $this->assertStringContainsString('Migration table not found.', $this->runCommand('migrate:reset'));

        $this->runCommand('migrate --step');
        $this->runCommand('migrate:reset');

        $this->assertSame(['migrations'], $this->tables());
        $this->assertSame(0, $this->ran());
    }

    public function testRefresh(): void
    {
        $this->runCommand('migrate --step');
        $this->db()->insert('users', ['a@b.c'], ['email']);

        $output = $this->runCommand('migrate:refresh --step=1');

        $this->assertStringContainsString('Rolled back:  1700000003_add_published_to_posts', $output);
        $this->assertStringNotContainsString('Rolled back:  1700000002', $output);
        $this->assertEquals(1, $this->db()->fetchColumn('SELECT COUNT(*) FROM users'));

        $this->runCommand('migrate:refresh');

        $this->assertEquals(0, $this->db()->fetchColumn('SELECT COUNT(*) FROM users'));
        $this->assertSame(3, $this->ran());
    }

    public function testFresh(): void
    {
        $this->db()->execute('CREATE TABLE other (id INTEGER)');
        $this->runCommand('migrate');

        $output = $this->runCommand('migrate:fresh');

        $this->assertStringContainsString('Dropped all tables successfully.', $output);
        $this->assertSame(['migrations', 'posts', 'users'], $this->tables());
        $this->assertSame(3, $this->ran());
    }

    public function testFreshEmptiesEveryConnectionOfTheMigrations(): void
    {
        // A migration on its own connection.
        $this->runCommand('migrate --path=migrations/reports');
        $this->runCommand('migrate:fresh --path=migrations/reports');

        $this->assertSame(['reports'], $this->tables(Services::DB . '.reports'));
        $this->assertSame(1, $this->ran());

        // The migrations on --database, the log on the default connection.
        $this->runCommand('migrate --database=reports');
        $this->output->out = '';
        $output = $this->runCommand('migrate:fresh --database=reports');

        $this->assertStringContainsString('Migrated:  1700000001_create_users_table', $output);
        $this->assertSame(['posts', 'users'], $this->tables(Services::DB . '.reports'));
        $this->assertSame(['migrations'], $this->tables());
        $this->assertSame(3, $this->ran());
    }

    public function testDatabaseOption(): void
    {
        $this->runCommand('migrate --database=reports');

        $this->assertSame(['migrations'], $this->tables());
        $this->assertSame(['posts', 'users'], $this->tables(Services::DB . '.reports'));
    }

    public function testMakeMigration(): void
    {
        $output = $this->runCommand('make:migration create_tags_table --path=' . self::MAKE_PATH);

        $this->assertMatchesRegularExpression('/Created Migration: \d+_create_tags_table/', $output);
        $files = glob(BASE_PATH . '/' . self::MAKE_PATH . '/*_create_tags_table.php') ?: [];
        $this->assertCount(1, $files);
        $this->assertStringContainsString("\$schema->create('tags'", (string) file_get_contents($files[0]));

        $this->runCommand('make:migration add_slug --table=tags --path=' . self::MAKE_PATH);
        $files = glob(BASE_PATH . '/' . self::MAKE_PATH . '/*_add_slug.php') ?: [];
        $this->assertStringContainsString("\$schema->table('tags'", (string) file_get_contents($files[0]));

        $this->runCommand('make:migration labels --create=labels --path=' . self::MAKE_PATH);
        $files = glob(BASE_PATH . '/' . self::MAKE_PATH . '/*_labels.php') ?: [];
        $this->assertStringContainsString("\$schema->create('labels'", (string) file_get_contents($files[0]));

        $this->runCommand('migrate --path=' . self::MAKE_PATH);
        $this->assertSame(['labels', 'migrations', 'tags'], $this->tables());
    }

    private function db(string $service = Services::DB): AdapterInterface
    {
        /** @var AdapterInterface */
        return $this->getDI()->getShared($service);
    }

    /**
     * @return list<string>
     */
    private function tables(string $service = Services::DB): array
    {
        $tables = array_values(array_filter($this->db($service)->listTables(), static fn(string $table): bool => !str_starts_with($table, 'sqlite_')));
        sort($tables);

        return $tables;
    }

    private function ran(): int
    {
        return (int) $this->db()->fetchColumn('SELECT COUNT(*) FROM migrations');
    }
}

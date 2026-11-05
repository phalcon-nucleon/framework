<?php

declare(strict_types=1);

namespace Test\Database\Schema;

use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Builder;
use Neutrino\Database\Schema\Definition;
use Neutrino\Database\Schema\Exception\CommandException;
use Neutrino\Database\Schema\Exception\UnknownCommandException;
use Neutrino\Database\Schema\Grammar\Sqlite as SqliteGrammar;
use Phalcon\Db\Adapter\Pdo\Sqlite;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BlueprintTest extends TestCase
{
    public function testColumns(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $blueprint->string('name', 50)->nullable();
        $blueprint->decimal('price', 2);
        $blueprint->enum('status', ['a', 'b']);
        $blueprint->timestamps();

        $this->assertSame('users', $blueprint->getTable());
        $this->assertSame(['id', 'name', 'price', 'status', 'created_at', 'updated_at'], array_keys($blueprint->getColumns()));
        $this->assertSame(['name' => 'id', 'type' => 'integer', 'autoIncrement' => true, 'unsigned' => true, 'primary' => true], $blueprint->getColumns()['id']->getAttributes());
        $this->assertSame(['name' => 'name', 'type' => 'string', 'size' => 50, 'nullable' => true], $blueprint->getColumns()['name']->getAttributes());
        $this->assertSame(['name' => 'price', 'type' => 'decimal', 'scale' => 2], $blueprint->getColumns()['price']->getAttributes());
        $this->assertSame(['a', 'b'], $blueprint->getColumns()['status']->get('values'));
        $this->assertSame('CURRENT_TIMESTAMP', $blueprint->getColumns()['updated_at']->get('onUpdate'));
    }

    public function testShortcuts(): void
    {
        $blueprint = new Blueprint('t');
        $blueprint->nullableTimestamps(3);
        $blueprint->softDeletesTz();
        $blueprint->rememberToken();
        $blueprint->morphs('owner', 'owner_index');

        $columns = $blueprint->getColumns();
        $this->assertTrue($columns['created_at']->get('nullable'));
        $this->assertSame(3, $columns['updated_at']->get('precision'));
        $this->assertSame('timestampTz', $columns['deleted_at']->get('type'));
        $this->assertSame(100, $columns['remember_token']->get('size'));
        $this->assertSame(['owner_id', 'owner_type'], $blueprint->getIndexes()[0]->get('columns'));
        $this->assertSame('owner_index', $blueprint->getIndexes()[0]->get('name'));
    }

    public function testIndexNames(): void
    {
        $blueprint = new Blueprint('my-table');
        $blueprint->unique(['a', 'b']);
        $blueprint->index('c', 'named');
        $blueprint->index('d', null, 'fulltext');
        $blueprint->primary('id');

        $this->assertSame(
            [['my_table_a_b_unique', 'UNIQUE'], ['named', 'INDEX'], ['my_table_d_fulltext', 'FULLTEXT'], ['my_table_id_primary', 'PRIMARY']],
            array_map(static fn(Definition $index): array => [$index->get('name'), $index->get('type')], $blueprint->getIndexes()),
        );
    }

    public function testCommands(): void
    {
        $blueprint = new Blueprint('t');
        $blueprint->dropColumns(['a', 'b']);
        $blueprint->renameColumn('c', 'd');
        $blueprint->dropPrimary();
        $blueprint->dropUnique('u');
        $blueprint->dropForeign(['f1', 'f2']);
        $blueprint->dropTimestamps();
        $blueprint->dropSoftDeletes();
        $blueprint->dropRememberToken();
        $blueprint->rename('u');
        $blueprint->sql('SELECT 1');

        $this->assertSame([
            ['name' => 'dropColumn', 'columns' => ['a', 'b']],
            ['name' => 'renameColumn', 'from' => 'c', 'to' => 'd'],
            ['name' => 'dropPrimary'],
            ['name' => 'dropIndex', 'index' => ['u']],
            ['name' => 'dropForeign', 'reference' => ['f1', 'f2']],
            ['name' => 'dropColumn', 'columns' => ['created_at', 'updated_at']],
            ['name' => 'dropColumn', 'columns' => ['deleted_at']],
            ['name' => 'dropColumn', 'columns' => ['remember_token']],
            ['name' => 'rename', 'to' => 'u'],
            ['name' => 'sql', 'sql' => 'SELECT 1'],
        ], array_map(static fn(Definition $command): array => $command->getAttributes(), $blueprint->getCommands()));
    }

    public function testOptions(): void
    {
        $blueprint = (new Blueprint('t'))->temporary()->option('engine', 'InnoDB')->option('Temporary', true);

        $this->assertSame(['temporary' => true, 'ENGINE' => 'InnoDB'], $blueprint->getOptions());
    }

    public function testNoAction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The blueprint of the table "t" has no action (create, update, drop…).');

        (new Blueprint('t'))->build(new Sqlite(['dbname' => ':memory:']), new SqliteGrammar());
    }

    public function testUnknownCommand(): void
    {
        $blueprint = new class ('t') extends Blueprint {
            public function custom(): Definition
            {
                return $this->addCommand('custom', ['value' => [1]]);
            }
        };
        $blueprint->raw()->custom();

        try {
            $blueprint->build(new Sqlite(['dbname' => ':memory:']), new SqliteGrammar());
            $this->fail('No exception.');
        } catch (UnknownCommandException $e) {
            $this->assertSame('Schema command "custom" on "t" failed.', $e->getMessage());
            $this->assertSame('custom', $e->command->get('name'));
            $this->assertStringContainsString("  - name : 'custom'", (string) $e);
            $this->assertStringContainsString('  - value : array', (string) $e);
        }
    }

    public function testForeignKeyWithoutTable(): void
    {
        $schema = new Builder(new Sqlite(['dbname' => ':memory:']));

        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('Schema command "create" on "posts": the foreign key on user_id needs ->references(…)->on(…).');

        $schema->create('posts', fn(Blueprint $table) => $table->foreign('user_id')->references('id'));
    }

    public function testCommandErrorKeepsTheCause(): void
    {
        $schema = new Builder(new Sqlite(['dbname' => ':memory:']));

        try {
            $schema->table('missing', fn(Blueprint $table) => $table->dropColumn('a'));
            $this->fail('No exception.');
        } catch (CommandException $e) {
            $this->assertStringStartsWith('Schema command "dropColumn" on "missing": ', $e->getMessage());
            $this->assertNotNull($e->getPrevious());
        }
    }
}

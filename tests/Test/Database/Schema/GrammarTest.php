<?php

declare(strict_types=1);

namespace Test\Database\Schema;

use InvalidArgumentException;
use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Definition;
use Neutrino\Database\Schema\Grammar;
use Phalcon\Db\Column;
use Phalcon\Db\Dialect;
use Phalcon\Db\DialectInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * SQL of each column type and command, written by the Phalcon dialect of each database from the grammar.
 */
final class GrammarTest extends TestCase
{
    /**
     * Column => [Blueprint method, arguments, modifiers, MySQL, PostgreSQL, SQLite].
     */
    private const array COLUMNS = [
        'increments'            => ['increments', [], [], 'INT(0) UNSIGNED NOT NULL AUTO_INCREMENT', 'SERIAL NOT NULL', 'INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT'],
        'smallIncrements'       => ['smallIncrements', [], [], 'SMALLINT(0) UNSIGNED NOT NULL AUTO_INCREMENT', 'SMALLSERIAL NOT NULL', 'INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT'],
        'bigIncrements'         => ['bigIncrements', [], [], 'BIGINT(0) UNSIGNED NOT NULL AUTO_INCREMENT', 'BIGSERIAL NOT NULL', 'INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT'],
        'boolean'               => ['boolean', [], [], 'TINYINT(1) NOT NULL', 'BOOLEAN NOT NULL', 'TINYINT NOT NULL'],
        'boolean default'       => ['boolean', [], ['default' => false], 'TINYINT(1) NOT NULL DEFAULT 0', 'BOOLEAN DEFAULT false NOT NULL', "TINYINT DEFAULT '0' NOT NULL"],
        'tinyInteger'           => ['tinyInteger', [], [], 'TINYINT(0) NOT NULL', 'SMALLINT NOT NULL', 'INTEGER NOT NULL'],
        'smallInteger'          => ['smallInteger', [], [], 'SMALLINT(0) NOT NULL', 'SMALLINT NOT NULL', 'INTEGER NOT NULL'],
        'mediumInteger'         => ['mediumInteger', [], [], 'MEDIUMINT(0) NOT NULL', 'INT NOT NULL', 'INTEGER NOT NULL'],
        'integer'               => ['integer', [], ['default' => 0], 'INT(0) NOT NULL DEFAULT 0', 'INT DEFAULT 0 NOT NULL', "INTEGER DEFAULT '0' NOT NULL"],
        'unsignedInteger'       => ['unsignedInteger', [], [], 'INT(0) UNSIGNED NOT NULL', 'INT NOT NULL', 'INTEGER NOT NULL'],
        'bigInteger'            => ['bigInteger', [], [], 'BIGINT(0) NOT NULL', 'BIGINT NOT NULL', 'BIGINT NOT NULL'],
        'decimal'               => ['decimal', [], [], 'DECIMAL(10,0) NOT NULL', 'NUMERIC(10,0) NOT NULL', 'DECIMAL(10,0) NOT NULL'],
        'decimal(8, 2)'         => ['decimal', [2, 8], [], 'DECIMAL(8,2) NOT NULL', 'NUMERIC(8,2) NOT NULL', 'DECIMAL(8,2) NOT NULL'],
        'double'                => ['double', [], [], 'DOUBLE NOT NULL', 'DOUBLE PRECISION NOT NULL', 'DOUBLE NOT NULL'],
        'float'                 => ['float', [], [], 'FLOAT NOT NULL', 'FLOAT NOT NULL', 'FLOAT NOT NULL'],
        'json'                  => ['json', [], [], 'JSON NOT NULL', 'JSON NOT NULL', 'TEXT NOT NULL'],
        'jsonb'                 => ['jsonb', [], [], 'JSON NOT NULL', 'JSONB NOT NULL', 'TEXT NOT NULL'],
        'char'                  => ['char', [2], [], 'CHAR(2) NOT NULL', 'CHARACTER(2) NOT NULL', 'CHARACTER(2) NOT NULL'],
        'string'                => ['string', [], [], 'VARCHAR(255) NOT NULL', 'CHARACTER VARYING(255) NOT NULL', 'VARCHAR(255) NOT NULL'],
        'string default'        => ['string', [20], ['default' => "it's"], "VARCHAR(20) NOT NULL DEFAULT 'it''s'", "CHARACTER VARYING(20) DEFAULT 'it''s' NOT NULL", "VARCHAR(20) DEFAULT 'it''s' NOT NULL"],
        'nullable'              => ['string', [10], ['nullable' => true], 'VARCHAR(10) NULL', 'CHARACTER VARYING(10) NULL', 'VARCHAR(10) NULL'],
        'text'                  => ['text', [], [], 'TEXT NOT NULL', 'TEXT NOT NULL', 'TEXT NOT NULL'],
        'mediumText'            => ['mediumText', [], [], 'MEDIUMTEXT NOT NULL', 'TEXT NOT NULL', 'TEXT NOT NULL'],
        'longText'              => ['longText', [], [], 'LONGTEXT NOT NULL', 'TEXT NOT NULL', 'TEXT NOT NULL'],
        'enum'                  => ['enum', [['a', "b'c"]], [], "ENUM('a','b''c') NOT NULL", "VARCHAR(3) CHECK (\"c\" IN ('a', 'b''c')) NOT NULL", "VARCHAR(3) CHECK (\"c\" IN ('a', 'b''c')) NOT NULL"],
        'blob'                  => ['blob', [], [], 'BLOB NOT NULL', 'BYTEA NOT NULL', 'BLOB NOT NULL'],
        'binary'                => ['binary', [], [], 'BLOB NOT NULL', 'BYTEA NOT NULL', 'BLOB NOT NULL'],
        'tinyBlob'              => ['tinyBlob', [], [], 'TINYBLOB NOT NULL', 'BYTEA NOT NULL', 'TINYBLOB NOT NULL'],
        'mediumBlob'            => ['mediumBlob', [], [], 'MEDIUMBLOB NOT NULL', 'BYTEA NOT NULL', 'MEDIUMBLOB NOT NULL'],
        'longBlob'              => ['longBlob', [], [], 'LONGBLOB NOT NULL', 'BYTEA NOT NULL', 'LONGBLOB NOT NULL'],
        'date'                  => ['date', [], [], 'DATE NOT NULL', 'DATE NOT NULL', 'DATE NOT NULL'],
        'dateTime'              => ['dateTime', [], [], 'DATETIME NOT NULL', 'TIMESTAMP NOT NULL', 'DATETIME NOT NULL'],
        'dateTime(6)'           => ['dateTime', [6], [], 'DATETIME(6) NOT NULL', 'TIMESTAMP(6) NOT NULL', 'DATETIME NOT NULL'],
        'dateTimeTz'            => ['dateTimeTz', [], [], 'DATETIME NOT NULL', 'TIMESTAMP WITH TIME ZONE NOT NULL', 'DATETIME NOT NULL'],
        'time'                  => ['time', [], [], 'TIME NOT NULL', 'TIME NOT NULL', 'TIME NOT NULL'],
        'time(3)'               => ['time', [3], [], 'TIME(3) NOT NULL', 'TIME(3) NOT NULL', 'TIME NOT NULL'],
        'timeTz'                => ['timeTz', [], [], 'TIME NOT NULL', 'TIME WITH TIME ZONE NOT NULL', 'TIME NOT NULL'],
        'timestamp'             => ['timestamp', [], [], 'TIMESTAMP NOT NULL', 'TIMESTAMP NOT NULL', 'TIMESTAMP NOT NULL'],
        'timestamp(6) current'  => ['timestamp', [6], ['default' => 'CURRENT_TIMESTAMP'], 'TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)', 'TIMESTAMP(6) DEFAULT CURRENT_TIMESTAMP NOT NULL', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL'],
        'timestamp on update'   => ['timestamp', [], ['default' => 'CURRENT_TIMESTAMP', 'onUpdate' => 'CURRENT_TIMESTAMP'], 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL'],
        'timestamp(3) update'   => ['timestamp', [3], ['default' => 'CURRENT_TIMESTAMP', 'onUpdate' => 'CURRENT_TIMESTAMP'], 'TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3) NOT NULL', 'TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP NOT NULL', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL'],
        'timestampTz'           => ['timestampTz', [], [], 'TIMESTAMP NOT NULL', 'TIMESTAMP WITH TIME ZONE NOT NULL', 'TIMESTAMP NOT NULL'],
        'uuid'                  => ['uuid', [], [], 'CHAR(36) NOT NULL', 'UUID NOT NULL', 'CHARACTER(36) NOT NULL'],
        'ipAddress'             => ['ipAddress', [], [], 'VARCHAR(45) NOT NULL', 'INET NOT NULL', 'VARCHAR(45) NOT NULL'],
        'macAddress'            => ['macAddress', [], [], 'VARCHAR(17) NOT NULL', 'MACADDR NOT NULL', 'VARCHAR(17) NOT NULL'],
        'after'                 => ['integer', [], ['after' => 'b'], 'INT(0) NOT NULL AFTER `b`', 'INT NOT NULL', 'INTEGER NOT NULL'],
        'first'                 => ['integer', [], ['first' => true], 'INT(0) NOT NULL FIRST', 'INT NOT NULL', 'INTEGER NOT NULL'],
    ];

    /**
     * @return iterable<string, array{Grammar, DialectInterface, string, list<mixed>, array<string, mixed>, string}>
     */
    public static function columns(): iterable
    {
        foreach (self::databases() as $index => [$grammar, $dialect, $prefix]) {
            foreach (self::COLUMNS as $name => $column) {
                yield $grammar::class . " $name" => [$grammar, $dialect, $column[0], $column[1], $column[2], $prefix . $column[3 + $index]];
            }
        }
    }

    /**
     * @param list<mixed>          $arguments
     * @param array<string, mixed> $modifiers
     */
    #[DataProvider('columns')]
    public function testColumn(Grammar $grammar, DialectInterface $dialect, string $method, array $arguments, array $modifiers, string $expected): void
    {
        $definition = (new Blueprint('t'))->{$method}('c', ...$arguments);
        foreach ($modifiers as $modifier => $value) {
            $definition->{$modifier}($value);
        }

        // The primary key is created apart (see BlueprintTest).
        $attributes = $definition->getAttributes();
        unset($attributes['primary']);

        $this->assertSame($expected, $dialect->addColumn('t', '', $grammar->column(new Definition($attributes))));
    }

    public function testColumnAttributes(): void
    {
        $column = (new Grammar\Mysql())->column(new Definition(['name' => 'c', 'type' => 'integer', 'unsigned' => true, 'comment' => 'note', 'primary' => true]));

        $this->assertSame(Column::TYPE_INTEGER, $column->getType());
        $this->assertTrue($column->isUnsigned());
        $this->assertTrue($column->isNotNull());
        $this->assertTrue($column->isPrimary());
        $this->assertSame('note', $column->getComment());
    }

    public function testUnknownType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column "c": unknown type "point".');

        (new Grammar\Mysql())->column(new Definition(['name' => 'c', 'type' => 'point']));
    }

    /**
     * @return iterable<string, array{Grammar, string, string}>
     */
    public static function commands(): iterable
    {
        $mysql = new Grammar\Mysql();
        $postgresql = new Grammar\Postgresql();
        $sqlite = new Grammar\Sqlite();

        yield 'mysql enable' => [$mysql, $mysql->enableForeignKeyConstraints(), 'SET FOREIGN_KEY_CHECKS=1'];
        yield 'mysql disable' => [$mysql, $mysql->disableForeignKeyConstraints(), 'SET FOREIGN_KEY_CHECKS=0'];
        yield 'mysql rename table' => [$mysql, $mysql->renameTable('a', 'b'), 'RENAME TABLE `a` TO `b`'];
        yield 'mysql rename table schema' => [$mysql, $mysql->renameTable('a', 'b', 's'), 'RENAME TABLE `s`.`a` TO `s`.`b`'];
        yield 'mysql rename column' => [$mysql, $mysql->renameColumn('t', 'a', 'b'), 'ALTER TABLE `t` RENAME COLUMN `a` TO `b`'];
        yield 'mysql add primary' => [$mysql, $mysql->addPrimary('t', ['a', 'b']), 'ALTER TABLE `t` ADD PRIMARY KEY (`a`, `b`)'];
        yield 'mysql drop primary' => [$mysql, $mysql->dropPrimary('t'), 'ALTER TABLE `t` DROP PRIMARY KEY'];
        yield 'mysql quoting' => [$mysql, $mysql->renameColumn('t', 'a`b', 'c'), 'ALTER TABLE `t` RENAME COLUMN `a``b` TO `c`'];

        yield 'postgresql enable' => [$postgresql, $postgresql->enableForeignKeyConstraints(), 'SET CONSTRAINTS ALL IMMEDIATE'];
        yield 'postgresql disable' => [$postgresql, $postgresql->disableForeignKeyConstraints(), 'SET CONSTRAINTS ALL DEFERRED'];
        yield 'postgresql rename table' => [$postgresql, $postgresql->renameTable('a', 'b', 's'), 'ALTER TABLE "s"."a" RENAME TO "b"'];
        yield 'postgresql rename column' => [$postgresql, $postgresql->renameColumn('t', 'a', 'b'), 'ALTER TABLE "t" RENAME COLUMN "a" TO "b"'];
        yield 'postgresql add primary' => [$postgresql, $postgresql->addPrimary('t', ['a']), 'ALTER TABLE "t" ADD CONSTRAINT "t_pkey" PRIMARY KEY ("a")'];
        yield 'postgresql drop primary' => [$postgresql, $postgresql->dropPrimary('t'), 'ALTER TABLE "t" DROP CONSTRAINT "t_pkey"'];
        yield 'postgresql quoting' => [$postgresql, $postgresql->renameColumn('t', 'a"b', 'c'), 'ALTER TABLE "t" RENAME COLUMN "a""b" TO "c"'];

        yield 'sqlite enable' => [$sqlite, $sqlite->enableForeignKeyConstraints(), 'PRAGMA foreign_keys = ON'];
        yield 'sqlite disable' => [$sqlite, $sqlite->disableForeignKeyConstraints(), 'PRAGMA foreign_keys = OFF'];
        yield 'sqlite rename table' => [$sqlite, $sqlite->renameTable('a', 'b'), 'ALTER TABLE "a" RENAME TO "b"'];
        yield 'sqlite rename column' => [$sqlite, $sqlite->renameColumn('t', 'a', 'b'), 'ALTER TABLE "t" RENAME COLUMN "a" TO "b"'];
        yield 'sqlite disable in transaction' => [$sqlite, $sqlite->disableForeignKeyConstraints(true), 'PRAGMA defer_foreign_keys = ON'];
        yield 'sqlite enable in transaction' => [$sqlite, $sqlite->enableForeignKeyConstraints(true), 'PRAGMA defer_foreign_keys = OFF'];
        yield 'mysql disable in transaction' => [$mysql, $mysql->disableForeignKeyConstraints(true), 'SET FOREIGN_KEY_CHECKS=0'];

        $id = new Column('id', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => true, 'primary' => true, 'notNull' => true]);
        yield 'mysql add primary column' => [$mysql, $mysql->addColumn('t', $id), 'ALTER TABLE `t` ADD `id` INT(0) NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)'];
        yield 'postgresql add primary column' => [$postgresql, $postgresql->addColumn('t', $id), 'ALTER TABLE "t" ADD COLUMN "id" SERIAL NOT NULL; ALTER TABLE "t" ADD CONSTRAINT "t_pkey" PRIMARY KEY ("id")'];
        yield 'sqlite add primary column' => [$sqlite, $sqlite->addColumn('t', $id), 'ALTER TABLE "t" ADD COLUMN "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT'];
        yield 'mysql add column' => [$mysql, $mysql->addColumn('t', new Column('a', ['type' => Column::TYPE_INTEGER])), 'ALTER TABLE `t` ADD `a` INT(0) NOT NULL'];
        yield 'postgresql widen serial' => [
            $postgresql,
            (string) $postgresql->modifyColumn('t', new Column('id', ['type' => Column::TYPE_BIGINTEGER, 'autoIncrement' => true, 'notNull' => true]), $id),
            'ALTER TABLE "t" ALTER COLUMN "id" TYPE BIGINT',
        ];
        yield 'postgresql resize' => [
            $postgresql,
            (string) $postgresql->modifyColumn('t', new Column('a', ['type' => Column::TYPE_VARCHAR, 'size' => 20, 'notNull' => true]), new Column('a', ['type' => Column::TYPE_VARCHAR, 'size' => 10, 'notNull' => true])),
            'ALTER TABLE "t" ALTER COLUMN "a" TYPE CHARACTER VARYING(20)',
        ];
        yield 'mysql modify' => [$mysql, (string) $mysql->modifyColumn('t', $id, $id), ''];
    }

    #[DataProvider('commands')]
    public function testCommand(Grammar $grammar, string $sql, string $expected): void
    {
        $this->assertSame($expected, $sql);
    }

    public function testDropTables(): void
    {
        $this->assertSame(['DROP TABLE IF EXISTS `a`', 'DROP TABLE IF EXISTS `b`'], (new Grammar\Mysql())->dropTables(['a', 'b']));
        $this->assertSame(['DROP TABLE IF EXISTS "a", "b" CASCADE'], (new Grammar\Postgresql())->dropTables(['a', 'b']));
        $this->assertSame([], (new Grammar\Postgresql())->dropTables([]));
        $this->assertSame(['DROP TABLE IF EXISTS "a"'], (new Grammar\Sqlite())->dropTables(['a']));
    }

    public function testFeatures(): void
    {
        $this->assertFalse((new Grammar\Mysql())->supportsSchemaTransactions());
        $this->assertTrue((new Grammar\Postgresql())->supportsSchemaTransactions());
        $this->assertTrue((new Grammar\Sqlite())->supportsSchemaTransactions());

        $this->assertTrue((new Grammar\Mysql())->primaryAsIndex());
        $this->assertFalse((new Grammar\Postgresql())->primaryAsIndex());

        $this->assertTrue((new Grammar\Sqlite())->isSystemTable('sqlite_sequence'));
        $this->assertFalse((new Grammar\Sqlite())->isSystemTable('users'));
        $this->assertFalse((new Grammar\Mysql())->isSystemTable('sqlite_sequence'));
    }

    public function testSqliteCannotChangeThePrimaryKey(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SQLite cannot drop the primary key of an existing table.');

        (new Grammar\Sqlite())->dropPrimary('t');
    }

    /**
     * @return list<array{Grammar, DialectInterface, string}>
     */
    private static function databases(): array
    {
        return [
            [new Grammar\Mysql(), new Dialect\Mysql(), 'ALTER TABLE `t` ADD `c` '],
            [new Grammar\Postgresql(), new Dialect\Postgresql(), 'ALTER TABLE "t" ADD COLUMN "c" '],
            [new Grammar\Sqlite(), new Dialect\Sqlite(), 'ALTER TABLE "t" ADD COLUMN "c" '],
        ];
    }
}

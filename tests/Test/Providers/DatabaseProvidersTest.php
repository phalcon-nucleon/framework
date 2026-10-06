<?php

declare(strict_types=1);

namespace Test\Providers;

use Neutrino\Constants\Services;
use Neutrino\Model\MetaDataStrategy;
use Neutrino\Providers;
use Neutrino\Support\Db;
use Phalcon\Db\Adapter\Pdo\Sqlite;
use Phalcon\Events\Manager;
use Phalcon\Mvc\Model\Manager as ModelsManager;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\MetaDataInterface;
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Test\Models\DatabaseTestCase;
use Test\Models\Stub\Article;

final class DatabaseProvidersTest extends DatabaseTestCase
{
    public function testSingleConnection(): void
    {
        $this->assertSame($this->di->getShared(Services::DB), $this->di->getShared('db.main'));
        $this->assertInstanceOf(Sqlite::class, Db::connection());
        $this->assertSame(Db::connection(), Db::connection('main'));
    }

    public function testConnectionsAreBuiltOnFirstUse(): void
    {
        $this->container(['database' => ['connections' => ['other' => ['adapter' => 'sqlite', 'config' => ['dbname' => ':memory:']]]]]);

        $this->assertFalse($this->di->getService('db.main')->isResolved());
        $this->assertFalse($this->di->getService('db.other')->isResolved());
        $this->assertNotSame($this->di->getShared('db.main'), $this->di->getShared('db.other'));
        $this->assertSame($this->di->getShared(Services::DB), $this->di->getShared('db.main'));
    }

    public function testModelOnAnotherConnection(): void
    {
        $this->container(['database' => ['connections' => ['other' => ['adapter' => Sqlite::class, 'options' => ['dbname' => ':memory:']]]]]);
        $this->createTables('db.other');
        $this->db('db.other')->execute("INSERT INTO articles (id, title_col) VALUES (1, 'other')");

        $this->assertSame(1, OtherArticle::count(), 'Read on db.other.');
        $this->assertSame('other', OtherArticle::findFirst()?->title);
    }

    public function testReadAndWriteConnections(): void
    {
        $this->container(['database' => ['connections' => ['replica' => ['adapter' => 'sqlite', 'config' => ['dbname' => ':memory:']]]]]);
        $this->createTables();
        $this->createTables('db.replica');
        $this->db('db.replica')->execute("INSERT INTO articles (id, title_col) VALUES (1, 'replica')");

        $this->assertTrue((new ReplicatedArticle(['title' => 'written']))->save());

        $this->assertSame(1, (int) $this->db()->fetchColumn('SELECT COUNT(*) FROM articles'), 'Written on db.');
        $this->assertSame('replica', ReplicatedArticle::findFirst()?->title, 'Read on db.replica.');
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidConfigs(): iterable
    {
        yield 'unknown adapter' => [['connections' => ['main' => ['adapter' => 'oracle']]], 'Database connection "main": unknown adapter'];
        yield 'not an adapter' => [['connections' => ['main' => ['adapter' => \stdClass::class]]], 'unknown adapter'];
        yield 'config not an array' => [['connections' => ['main' => ['adapter' => 'sqlite', 'config' => 'x']]], '"config" must be an array'];
    }

    /**
     * @param array<string, mixed> $database
     */
    #[DataProvider('invalidConfigs')]
    public function testInvalidConnection(array $database, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        Providers\Database::connect('main', $database['connections']['main']);
    }

    public function testDefaultConnectionMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('the default connection "nope"');

        $this->container(['database' => ['default' => 'nope']]);
    }

    public function testModelServices(): void
    {
        $this->assertInstanceOf(ModelsManager::class, $this->di->getShared(Services::MODELS_MANAGER));
        $this->assertSame($this->di->getShared(Services::MODELS_MANAGER), $this->di->getShared(ModelsManager::class));
        $this->assertInstanceOf(TransactionManager::class, $this->di->getShared(Services::TRANSACTION_MANAGER));

        $metaData = $this->di->getShared(Services::MODELS_METADATA);
        $this->assertInstanceOf(MetaData\Memory::class, $metaData);
        $this->assertInstanceOf(MetaDataStrategy::class, $metaData->getStrategy());
        $this->assertSame($metaData, $this->di->getShared(MetaDataInterface::class));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, class-string}>
     */
    public static function metaDataAdapters(): iterable
    {
        yield 'memory' => [['adapter' => 'memory'], MetaData\Memory::class];
        yield 'apcu' => [['adapter' => 'apcu', 'options' => ['prefix' => 'test']], MetaData\Apcu::class];
        yield 'stream' => [['adapter' => 'stream', 'options' => ['metaDataDir' => '/tmp/']], MetaData\Stream::class];
        yield 'class' => [['adapter' => MetaData\Memory::class], MetaData\Memory::class];
    }

    /**
     * @param array<string, mixed> $metadata
     * @param class-string         $class
     */
    #[DataProvider('metaDataAdapters')]
    public function testMetaDataAdapter(array $metadata, string $class): void
    {
        $this->container(['models' => ['metadata' => $metadata]]);

        $this->assertInstanceOf($class, $this->di->getShared(Services::MODELS_METADATA));
    }

    public function testCachedMetaData(): void
    {
        if (!extension_loaded('apcu') || !apcu_enabled()) {
            $this->markTestSkipped('APCu is not available.');
        }

        $prefix = 'nucleon-test-' . getmypid() . '-' . bin2hex(random_bytes(3));
        $this->container(['models' => ['metadata' => ['adapter' => 'apcu', 'options' => ['prefix' => $prefix]]]]);
        $this->createTables();
        Article::find();

        // Next request: the meta-data comes from APCu, the strategy is not called.
        $this->container(['models' => ['metadata' => ['adapter' => 'apcu', 'options' => ['prefix' => $prefix]]]]);
        $this->createTables();
        $this->di->getShared(Services::MODELS_METADATA)->setStrategy(new FailingStrategy());

        $this->assertCount(0, Article::find());
    }

    public function testUnknownMetaDataAdapter(): void
    {
        $this->container(['models' => ['metadata' => ['adapter' => 'files']]]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Models meta-data: unknown adapter');

        $this->di->getShared(Services::MODELS_METADATA);
    }

    public function testGetQueriesAndPretend(): void
    {
        $db = $this->db();
        $manager = $db->getEventsManager();

        $queries = Db::getQueries(static function () use ($db): void {
            $db->execute("INSERT INTO articles (id, title_col) VALUES (1, 'a')");
        });
        $this->assertSame(["INSERT INTO articles (id, title_col) VALUES (1, 'a')"], $queries);
        $this->assertSame(1, (int) $db->fetchColumn('SELECT COUNT(*) FROM articles'));

        $pretended = Db::pretend(static function () use ($db): void {
            $db->execute('DELETE FROM articles');
        });
        $this->assertSame(['DELETE FROM articles'], $pretended);
        $this->assertSame(1, (int) $db->fetchColumn('SELECT COUNT(*) FROM articles'), 'Not run.');
        $this->assertSame($manager, $db->getEventsManager(), 'The events manager is kept.');
    }

    public function testGetQueriesRestoresAConnectionWithoutEventsManager(): void
    {
        $this->container();
        /** @var Sqlite $db */
        $db = $this->di->getShared(Services::DB);

        Db::pretend(static fn() => $db->execute('SELECT 1'));

        $this->assertNull($db->getEventsManager());
    }

    public function testGetQueriesDetachesOnError(): void
    {
        $db = $this->db();
        /** @var Manager $manager */
        $manager = $db->getEventsManager();
        $listeners = count($manager->getListeners('db:beforeQuery'));

        try {
            Db::getQueries(static fn() => throw new RuntimeException('boom'));
        } catch (RuntimeException) {
        }

        $this->assertCount($listeners, $manager->getListeners('db:beforeQuery'));
    }
}

final class OtherArticle extends Article
{
    public function initialize()
    {
        parent::initialize();
        $this->setConnectionService('db.other');
    }
}

final class ReplicatedArticle extends Article
{
    public function initialize()
    {
        parent::initialize();
        $this->setReadConnectionService('db.replica');
        $this->setWriteConnectionService('db');
    }
}

final class FailingStrategy implements MetaData\Strategy\StrategyInterface
{
    public function getColumnMaps(\Phalcon\Mvc\ModelInterface $model, \Phalcon\Di\DiInterface $container): array
    {
        throw new RuntimeException('The cached meta-data should be used.');
    }

    public function getMetaData(\Phalcon\Mvc\ModelInterface $model, \Phalcon\Di\DiInterface $container): array
    {
        throw new RuntimeException('The cached meta-data should be used.');
    }
}

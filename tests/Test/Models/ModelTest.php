<?php

declare(strict_types=1);

namespace Test\Models;

use Neutrino\Constants\Services;
use Neutrino\Model\Description;
use Phalcon\Db\Column;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\MetaDataInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Test\Models\Stub\Article;
use Test\Models\Stub\Author;
use Test\Models\Stub\PlainModel;

final class ModelTest extends DatabaseTestCase
{
    public function testDescription(): void
    {
        $metaData = $this->metaData();
        $article = new Article();

        $this->assertSame(['id', 'title_col', 'summary', 'views', 'rating', 'published', 'created_at', 'updated_at', 'deleted'], $metaData->getAttributes($article));
        $this->assertSame(['id'], $metaData->getPrimaryKeyAttributes($article));
        $this->assertSame('id', $metaData->getIdentityField($article));
        $this->assertSame(['id', 'title_col', 'views', 'published', 'created_at', 'deleted'], $metaData->getNotNullAttributes($article));
        $this->assertSame(['summary' => true, 'rating' => true, 'updated_at' => true], $metaData->getEmptyStringAttributes($article));
        $this->assertSame(['views' => 0, 'published' => false, 'deleted' => false], $metaData->getDefaultValues($article));
        $this->assertSame(['id' => true], $metaData->getAutomaticCreateAttributes($article));
        $this->assertSame(['id' => true, 'views' => true, 'rating' => true], $metaData->getDataTypesNumeric($article));
        $this->assertSame(Column::TYPE_VARCHAR, $metaData->getDataTypes($article)['title_col']);
        $this->assertSame(['id' => 'id', 'title_col' => 'title', 'summary' => 'summary', 'views' => 'views', 'rating' => 'rating', 'published' => 'published', 'created_at' => 'created_at', 'updated_at' => 'updated_at', 'deleted' => 'deleted'], $metaData->getColumnMap($article));
        $this->assertSame('title_col', $metaData->getReverseColumnMap($article)['title'] ?? null);
    }

    /**
     * @return iterable<string, array{int|null, int, bool}>
     */
    public static function bindTypes(): iterable
    {
        foreach ([Column::TYPE_BIGINTEGER, Column::TYPE_INTEGER, Column::TYPE_MEDIUMINTEGER, Column::TYPE_SMALLINTEGER, Column::TYPE_TINYINTEGER, Column::TYPE_TIMESTAMP] as $type) {
            yield "integer $type" => [$type, Column::BIND_PARAM_INT, true];
        }
        foreach ([Column::TYPE_DECIMAL, Column::TYPE_FLOAT, Column::TYPE_DOUBLE] as $type) {
            yield "decimal $type" => [$type, Column::BIND_PARAM_DECIMAL, true];
        }
        foreach ([Column::TYPE_JSON, Column::TYPE_TEXT, Column::TYPE_CHAR, Column::TYPE_VARCHAR, Column::TYPE_DATE, Column::TYPE_DATETIME, Column::TYPE_TIME, Column::TYPE_ENUM, Column::TYPE_UUID] as $type) {
            yield "string $type" => [$type, Column::BIND_PARAM_STR, false];
        }
        foreach ([Column::TYPE_BLOB, Column::TYPE_JSONB, Column::TYPE_MEDIUMBLOB, Column::TYPE_TINYBLOB, Column::TYPE_LONGBLOB, Column::TYPE_BINARY] as $type) {
            yield "blob $type" => [$type, Column::BIND_PARAM_BLOB, false];
        }
        yield 'boolean' => [Column::TYPE_BOOLEAN, Column::BIND_PARAM_BOOL, false];
        yield 'null' => [null, Column::BIND_PARAM_NULL, false];
        yield 'unknown' => [999, Column::BIND_SKIP, false];
    }

    #[DataProvider('bindTypes')]
    public function testBindTypes(?int $type, int $bind, bool $numeric): void
    {
        $this->assertSame([$bind, $numeric], Description::bindType($type));

        $metaData = (new Description())->column('c', $type)->metaData();
        $this->assertSame($bind, $metaData[MetaData::MODELS_DATA_TYPES_BIND]['c']);
        $this->assertSame($numeric, isset($metaData[MetaData::MODELS_DATA_TYPES_NUMERIC]['c']));
    }

    public function testPrimaryOptions(): void
    {
        $metaData = (new Description())->primary('a', Column::TYPE_INTEGER, ['identity' => false, 'autoIncrement' => false])->primary('b', Column::TYPE_VARCHAR, ['identity' => false, 'autoIncrement' => false])->metaData();

        $this->assertSame(['a', 'b'], $metaData[MetaData::MODELS_PRIMARY_KEY]);
        $this->assertFalse($metaData[MetaData::MODELS_IDENTITY_COLUMN]);
        $this->assertSame([], $metaData[MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT]);
    }

    public function testCrud(): void
    {
        $article = new Article();
        $article->title = 'Nucleon 2.0';
        $article->summary = null;
        $article->rating = 4.5;

        $this->assertTrue($article->save(), implode(', ', $article->getMessages()));
        $this->assertSame(1, (int) $article->id);
        $this->assertNotEmpty($article->created_at, 'Timestamps on create.');
        $this->assertSame(1, Article::count());

        $found = Article::findFirst(['[title] = :t:', 'bind' => ['t' => 'Nucleon 2.0']]);
        $this->assertInstanceOf(Article::class, $found);
        $this->assertSame('4.5', (string) $found->rating);

        $found->views = 10;
        $this->assertTrue($found->save());
        $this->assertNotEmpty($found->updated_at, 'Timestamps on update.');
        $this->assertSame(10, (int) $this->db()->fetchColumn('SELECT views FROM articles'));

        $this->assertTrue($found->delete());
        $this->assertSame(1, (int) $this->db()->fetchColumn('SELECT deleted FROM articles'), 'Soft delete.');
        $this->assertSame(1, (int) $this->db()->fetchColumn('SELECT COUNT(*) FROM articles'));
    }

    public function testAttributes(): void
    {
        $metaData = $this->metaData();
        $author = new Author();

        $this->assertSame(['author_id', 'name', 'mail', 'level', 'created_at', 'updated_at', 'removed'], $metaData->getAttributes($author));
        $this->assertSame(['author_id'], $metaData->getPrimaryKeyAttributes($author));
        $this->assertSame(['mail' => true, 'updated_at' => true], $metaData->getEmptyStringAttributes($author));
        $this->assertSame(['level' => 1, 'removed' => false], $metaData->getDefaultValues($author));
        $this->assertSame('id', $metaData->getColumnMap($author)['author_id'] ?? null);
        $this->assertSame('email', $metaData->getColumnMap($author)['mail'] ?? null);

        $author->name = 'Ada';
        $author->email = 'ada@example.com';
        $this->assertTrue($author->save(), implode(', ', $author->getMessages()));
        $this->assertSame(1, $author->id);
        $this->assertNotEmpty($author->created_at);

        $found = Author::findFirst(['[email] = :e:', 'bind' => ['e' => 'ada@example.com']]);
        $this->assertInstanceOf(Author::class, $found);
        $this->assertSame('Ada', $found->name);

        $found->delete();
        $this->assertSame(1, (int) $this->db()->fetchColumn('SELECT removed FROM authors'));
    }

    public function testNoIntrospectionQuery(): void
    {
        (new Article(['title' => 'a']))->save();
        Article::find();
        (new Author(['name' => 'b']))->save();
        Author::findFirst();

        $this->assertNotSame([], $this->queries);
        $this->assertSame([], $this->introspectionQueries());
    }

    public function testOtherModelsAreIntrospected(): void
    {
        PlainModel::find();

        $this->assertNotSame([], $this->introspectionQueries());
    }

    public function testDescribedOnce(): void
    {
        new Article();
        new Article();

        $this->assertCount(9, Article::description()->metaData()[MetaData::MODELS_ATTRIBUTES]);
    }

    public function testTimestampableOptions(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('needs to have at least insert or update');

        new class extends \Neutrino\Model {
            public function initialize()
            {
                parent::initialize();
                $this->timestampable('stamp');
            }
        };
    }

    private function metaData(): MetaDataInterface
    {
        /** @var MetaDataInterface */
        return $this->di->getShared(Services::MODELS_METADATA);
    }
}

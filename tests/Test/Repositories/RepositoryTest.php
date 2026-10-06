<?php

declare(strict_types=1);

namespace Test\Repositories;

use InvalidArgumentException;
use Neutrino\Repositories\Repository;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Test\Models\DatabaseTestCase;
use Test\Models\Stub\Article;

final class RepositoryTest extends DatabaseTestCase
{
    private ArticleRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([[1, 'Alpha', 10, 4.5], [2, 'Beta', 20, null], [3, 'Gamma', 30, 3.0], [4, 'al%', 0, 1.0]] as [$id, $title, $views, $rating]) {
            $this->db()->insert('articles', [$id, $title, $views, $rating], ['id', 'title_col', 'views', 'rating']);
        }

        $this->repository = new ArticleRepository();
    }

    public function testModelClassIsRequired(): void
    {
        $this->expectException(RuntimeException::class);

        new class extends Repository {};
    }

    public function testQueries(): void
    {
        $this->assertCount(4, $this->repository->all());
        $this->assertSame(4, $this->repository->count());
        $this->assertSame(2, $this->repository->count(['views' => ['operator' => '>=', 'value' => 20]]));
        $this->assertSame(['Beta', 'Gamma'], $this->titles($this->repository->find(['id' => [2, 3]], ['id'])));
        $this->assertSame(['Gamma', 'Beta'], $this->titles($this->repository->find([], ['views' => 'desc'], 2)));
        $this->assertSame(['Beta'], $this->titles($this->repository->find([], ['id'], 1, 1)));
        $this->assertSame('Gamma', $this->repository->first(['title' => 'Gamma'])?->title);
        $this->assertNull($this->repository->first(['title' => 'Nope']));
        $this->assertSame(['Beta'], $this->titles($this->repository->find(['rating' => null])));
        $this->assertSame(['Alpha', 'Gamma'], $this->titles($this->repository->find(['rating' => ['operator' => 'IS NOT NULL', 'value' => null], 'views' => ['operator' => 'NOT IN', 'value' => [0]]], ['id'])));
        $this->assertEquals(15, $this->repository->average('views'));
        $this->assertEquals(0, $this->repository->minimum('views'));
        $this->assertEquals(30, $this->repository->maximum('views', ['id' => [1, 2, 3]]));
    }

    public function testAStringIsAnEquality(): void
    {
        // 1.3: LIKE, where "al%" matched Alpha.
        $this->assertSame(['al%'], $this->titles($this->repository->find(['title' => 'al%'])));
        $this->assertSame(['Alpha', 'al%'], $this->titles($this->repository->find(['title' => ['operator' => 'like', 'value' => 'al%']], ['id'])));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<int|string, string>|null, string}>
     */
    public static function injections(): iterable
    {
        yield 'column' => [['id = 1 OR 1' => 1], null, 'unknown attribute "id = 1 OR 1"'];
        yield 'database column instead of attribute' => [['title_col' => 'a'], null, 'unknown attribute "title_col"'];
        yield 'operator' => [['id' => ['operator' => '= 1 OR 1 =', 'value' => 1]], null, 'unknown operator'];
        yield 'order column' => [[], ['id; DROP TABLE articles'], 'unknown attribute'];
        yield 'order direction' => [[], ['id' => 'ASC, (SELECT 1)'], 'unknown sort direction'];
    }

    /**
     * @param array<string, mixed>           $params
     * @param array<int|string, string>|null $order
     */
    #[DataProvider('injections')]
    public function testInjectionsAreRejected(array $params, ?array $order, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->repository->find($params, $order);
    }

    public function testFirstOrNewAndCreate(): void
    {
        $this->assertSame(1, (int) $this->repository->firstOrNew(['title' => 'Alpha'])->id);

        $new = $this->repository->firstOrNew(['title' => 'Delta']);
        $this->assertNull($new->readAttribute('id'));
        $this->assertSame('Delta', $new->title);

        $created = $this->repository->firstOrCreate(['title' => 'Epsilon'], true);
        $this->assertNotNull($created->id);
        $this->assertSame(5, $this->repository->count());
    }

    public function testWritesInATransaction(): void
    {
        $a = new Article(['title' => 'One']);
        $b = new Article(['title' => 'Two']);

        $this->assertTrue($this->repository->create([$a, $b]));
        $this->assertSame(6, $this->repository->count());

        $a->views = 99;
        $this->assertTrue($this->repository->update($a));
        $this->assertSame(1, $this->repository->count(['views' => 99]));

        $this->assertTrue($this->repository->save($b, false));
        $this->assertTrue($this->repository->delete([$a, $b]));
    }

    public function testFailedWriteIsRolledBack(): void
    {
        $valid = new Article(['title' => 'Valid']);
        $invalid = new Article(); // title is required

        $this->assertFalse($this->repository->create([$valid, $invalid]));
        $this->assertSame(4, $this->repository->count(), 'Rolled back.');
        $this->assertNotSame([], $this->repository->getMessages());
        $this->assertStringContainsString('title is required', implode(', ', array_map('strval', $this->repository->getMessages())));
    }

    public function testEach(): void
    {
        $titles = [];
        foreach ($this->repository->each([], 1, null, 2, ['id']) as $article) {
            $titles[] = $article->title;
        }

        $this->assertSame(['Beta', 'Gamma', 'al%'], $titles);
    }

    /**
     * @param iterable<Article> $articles
     *
     * @return list<string>
     */
    private function titles(iterable $articles): array
    {
        $titles = [];
        foreach ($articles as $article) {
            $titles[] = (string) $article->title;
        }

        return $titles;
    }
}

/**
 * @extends Repository<Article>
 */
final class ArticleRepository extends Repository
{
    protected ?string $modelClass = Article::class;
}

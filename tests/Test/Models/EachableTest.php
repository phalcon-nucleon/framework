<?php

declare(strict_types=1);

namespace Test\Models;

use PHPUnit\Framework\Attributes\DataProvider;
use Test\Models\Stub\Article;

final class EachableTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        for ($i = 1; $i <= 25; $i++) {
            $this->db()->execute("INSERT INTO articles (id, title_col) VALUES ($i, 'a$i')");
        }
        $this->queries = [];
    }

    /**
     * @return iterable<string, array{int|null, int|null, int, list<int>, int}>
     */
    public static function ranges(): iterable
    {
        yield 'all, pages of 10' => [null, null, 10, range(1, 25), 3];
        yield 'all, exact pages' => [null, null, 5, range(1, 25), 6];
        yield 'from 20' => [20, null, 10, range(21, 25), 1];
        yield 'from 3 to 8' => [3, 8, 2, range(4, 8), 3];
        yield 'empty range' => [5, 5, 10, [], 0];
    }

    /**
     * @param list<int> $ids
     */
    #[DataProvider('ranges')]
    public function testEach(?int $start, ?int $end, int $pad, array $ids, int $queries): void
    {
        $found = [];
        foreach (Article::each(['order' => 'id'], $start, $end, $pad) as $index => $article) {
            $this->assertSame(count($found), $index);
            $found[] = (int) $article->id;
        }

        $this->assertSame($ids, $found);
        $this->assertCount($queries, $this->queries);
    }

    public function testLimitOfTheCriteriaIsThePad(): void
    {
        $this->assertCount(25, iterator_to_array(Article::each(['order' => 'id', 'limit' => 7])));
        $this->assertCount(4, $this->queries);
    }
}

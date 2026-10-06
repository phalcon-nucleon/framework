<?php

declare(strict_types=1);

namespace Neutrino\Support\Model;

use Generator;

/**
 * `foreach (User::each(['active = 1']) as $user)`: iterates over the models, `$pad` rows per query, from the row
 * `$start` to `$end`.
 *
 * @mixin \Phalcon\Mvc\Model<mixed>
 */
trait Eachable
{
    /**
     * @param array<int|string, mixed>|null $criteria Criteria of `find()`; its `limit` replaces `$pad`
     *
     * @return Generator<int, static>
     */
    public static function each(?array $criteria = null, ?int $start = null, ?int $end = null, int $pad = 100): Generator
    {
        $criteria ??= [];
        $start ??= 0;
        $pad = isset($criteria['limit']) && is_int($criteria['limit']) ? $criteria['limit'] : $pad;

        if ($pad < 1 || ($end !== null && $start >= $end)) {
            return;
        }

        $index = 0;

        for ($offset = $start; $end === null || $offset < $end; $offset += $pad) {
            $criteria['limit'] = $end === null ? $pad : min($pad, $end - $offset);
            $criteria['offset'] = $offset;
            $count = 0;

            foreach (static::find($criteria) as $model) {
                $count++;

                /** @var static $model */
                yield $index++ => $model;
            }

            // A partial page is the last one.
            if ($count < $criteria['limit']) {
                return;
            }
        }
    }
}

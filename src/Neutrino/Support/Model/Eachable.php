<?php

namespace Neutrino\Support\Model;


trait Eachable
{
    /**
     * Use as :
     * foreach(Model::each() as $model){
     *     // ... do some stuff
     * }
     *
     * @param array    $criteria
     * @param null|int $start
     * @param null|int $end
     * @param int      $pad
     *
     * @return \Generator|\Neutrino\Model[]|\Phalcon\Mvc\Model[]
     */
    public static function each(array $criteria = null, $start = null, $end = null, $pad = 100)
    {
        /** @var \Neutrino\Model $this */

        if (is_null($start)) {
            $start = 0;
        }

        if (is_null($end)) {
            $end = INF;
        }

        if ($start >= $end) {
            return;
        }

        if (empty($criteria['limit'])) {
            $criteria['limit'] = $pad;
        } else {
            $pad = $criteria['limit'];
        }

        $nb = ceil(($end - $start) / $pad);
        $idx = 0;
        $page = 0;
        do {
            $finish = true;

            $criteria['offset'] = $start + ($pad * $page);

            $models = self::find($criteria);

            foreach ($models as $model) {
                $finish = false;

                yield $idx => $model;

                $idx++;
            }

            $page++;

            if ($page >= $nb) {
                $finish = true;
            }
        } while (!$finish);
    }
}
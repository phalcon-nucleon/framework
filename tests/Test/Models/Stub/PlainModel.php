<?php

declare(strict_types=1);

namespace Test\Models\Stub;

use Phalcon\Mvc\Model;

/**
 * A Phalcon model: introspected.
 */
class PlainModel extends Model
{
    public function initialize(): void
    {
        $this->setSource('plain');
    }
}

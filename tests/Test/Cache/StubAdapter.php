<?php

declare(strict_types=1);

namespace Test\Cache;

use Phalcon\Cache\Adapter\Memory;

/**
 * A custom cache adapter.
 */
final class StubAdapter extends Memory {}

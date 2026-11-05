<?php

declare(strict_types=1);

/**
 * Laravel 5.4 Fluent Class
 *
 * @see https://github.com/illuminate/support/blob/401bb82931e22bb8e8de727f3bde9cff7d186821/Fluent.php
 */

namespace Neutrino\Support;

use Neutrino\Support\Fluent\Fluentable;
use Neutrino\Support\Fluent\Fluentize;

/**
 * Attribute container with a fluent API.
 */
class Fluent implements Fluentable
{
    use Fluentize;
}

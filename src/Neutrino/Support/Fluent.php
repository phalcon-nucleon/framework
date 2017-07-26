<?php
/**
 * Laravel 5.4 Fluent Class
 *
 * @see https://github.com/illuminate/support/blob/401bb82931e22bb8e8de727f3bde9cff7d186821/Fluent.php
 */

namespace Neutrino\Support;

use Neutrino\Support\Fluent\Fluentable;
use Neutrino\Support\Fluent\Fluentize;

/**
 * Class Fluent
 *
 * @package Neutrino\Support
 */
class Fluent implements Fluentable
{
    use Fluentize;
}

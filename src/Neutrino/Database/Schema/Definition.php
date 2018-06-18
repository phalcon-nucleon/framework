<?php

namespace Neutrino\Database\Schema;

use Neutrino\Support\Fluent;

/**
 * Provide IDE auto-completion
 *
 * Class Definition
 *
 * @package Neutrino\Database\Schema\Fluents
 *
 * Column modifier methods
 * @method $this default(\mixed $value)
 * @method $this nullable($nullable = true)
 * @method $this unsigned()
 * @method $this autoIncrement()
 * @method $this comment(\string $comment)
 * @method $this first()
 * @method $this after(\string $column)
 *
 * Index methods
 * @method $this primary(\string $name = null)
 * @method $this unique(\string $name = null)
 * @method $this index(\string $name = null)
 *
 * ForeignKey methods
 * @method $this foreign()
 * @method $this on(\string|array $name)
 * @method $this references(\string|array $name)
 * @method $this onUpdate(\string $action)
 * @method $this onDelete(\string $action)
 */
class Definition extends Fluent
{

}

<?php

declare(strict_types=1);

namespace Test\Models\Stub;

use Neutrino\Model;
use Neutrino\Support\Model\Eachable;
use Phalcon\Db\Column;

/**
 * Described in initialize().
 *
 * @property int         $id
 * @property string      $title
 * @property string|null $summary
 * @property int         $views
 * @property float|null  $rating
 * @property bool        $published
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property bool        $deleted
 */
class Article extends Model
{
    use Eachable;

    public function initialize()
    {
        parent::initialize();

        $this->setSource('articles');

        $this->primary('id', Column::TYPE_INTEGER);
        $this->column('title_col', Column::TYPE_VARCHAR, ['map' => 'title']);
        $this->column('summary', Column::TYPE_TEXT, ['nullable' => true]);
        $this->column('views', Column::TYPE_INTEGER, ['default' => 0]);
        $this->column('rating', Column::TYPE_DECIMAL, ['nullable' => true]);
        $this->column('published', Column::TYPE_BOOLEAN, ['default' => false]);
        $this->timestamps();
        $this->softDelete();
    }
}

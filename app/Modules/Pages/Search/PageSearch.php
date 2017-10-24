<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 11/11/14.11.2014 11:00
 */

namespace Modules\Pages\Search;

use Modules\Pages\Models\Page;
use Modules\Search\Components\SearchIndex;

class PageSearch extends SearchIndex
{
    public $fields = [
        'name',
        'slug',
        'content_short',
        'content',
        'published_at'
    ];

    /**
     * @return array
     */
    public function getParams()
    {
        return [
            "_all" => ["analyzer" => "my_analyzer"],
        ];
    }

    public function getModel()
    {
        return new Page;
    }

    public function getQuerySet()
    {
        return $this->getModel()->objects()->published();
    }
}

 
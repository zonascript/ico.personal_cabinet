<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 03/07/14.07.2014 16:41
 */

namespace Modules\Pages\Sitemap;

use Modules\Pages\Models\Page;
use Modules\Sitemap\Components\Sitemap;

/**
 * Class PageSitemap
 * @package Modules\Pages
 */
class PageSitemap extends Sitemap
{
    public function getPriority($data)
    {
        return '0.4';
    }

    public function getModelClass()
    {
        return Page::class;
    }

    public function getLastMod($data)
    {
        $date = isset($data['updated_at']) ? $data['updated_at'] : $data['created_at'];
        return $this->formatLastMod($date);
    }

    public function getQuerySet()
    {
        return parent::getQuerySet()->order(['root', 'lft']);
    }

    public function getLoc($data)
    {
        return $this->reverse('page:view', $data['is_index'] ? ['/'] : ['url' => $data['url']]);
    }
}

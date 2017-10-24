<?php

/**
 * All rights reserved.
 * 
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 27/08/14.08.2014 17:33
 */

namespace Modules\Pages\Models;

use Mindy\Orm\TreeManager;

/**
 * Class PageManager
 * @package Modules\Pages
 */
class PageManager extends TreeManager
{
    /**
     * @return \Mindy\Orm\TreeQuerySet
     */
    public function published()
    {
        return $this->filter(['is_published' => true]);
    }
}

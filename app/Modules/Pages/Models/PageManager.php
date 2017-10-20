<?php

namespace Modules\Pages\Models;

use Xcart\App\Orm\TreeManager;

/**
 * Class PageManager
 * @package Modules\Pages
 */
class PageManager extends TreeManager
{
    /**
     * @return \Xcart\App\Orm\TreeManager
     */
    public function published()
    {
        return $this->filter(['is_published' => true]);
    }
}

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
 * @date 04/09/14.09.2014 13:50
 */

namespace Modules\Sites\Traits;


use Mindy\Base\Mindy;

trait SiteTrait
{
    public function getCurrentSiteId()
    {
        return $this->getCurrentSite()->pk;
    }

    public function getCurrentSite()
    {
        return Mindy::app()->getModule('Sites')->getSite();
    }
}

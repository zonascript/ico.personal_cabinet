<?php

/**
 * Created by max
 * Date: 28/10/15
 * Time: 18:29
 */

namespace Modules\Sites\Models;

use Mindy\Base\Mindy;
use Mindy\Orm\Manager;

class CurrentSiteManager extends Manager
{
    public function currentSite()
    {
        /** @var \Modules\Sites\SitesModule $site */
        $site = Mindy::app()->getModule('Sites');
        $this->filter(['site' => $site->getSite()]);
        return $this;
    }
}
<?php

namespace Modules\Meta\Models;


use Xcart\App\Main\Xcart;
use Xcart\App\Orm\Manager;

class MetaManager extends Manager
{
//    use SiteTrait;

    public function currentSite()
    {
        /** @var \Modules\Sites\SitesModule $module */


        return $this;
    }
}

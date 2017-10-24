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
 * @date 15/09/14.09.2014 14:21
 */

namespace Modules\Meta\Models;

use Mindy\Orm\Manager;
use Modules\Sites\Traits\SiteTrait;

class MetaManager extends Manager
{
    use SiteTrait;

    public function currentSite()
    {
        $this->filter(['site' => $this->getCurrentSite()]);
        return $this;
    }
}

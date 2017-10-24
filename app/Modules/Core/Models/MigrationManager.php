<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 08/01/15 18:34
 */

namespace Modules\Core\Models;

use Mindy\Orm\Manager;

class MigrationManager extends Manager
{
    public function last()
    {
        $this->limit(1)->offset(0)->order(['-timestamp']);
        return $this;
    }
}

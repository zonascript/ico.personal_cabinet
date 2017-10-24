<?php

/**
 * User: max
 * Date: 27/08/15
 * Time: 18:33
 */

namespace Modules\Mail\Models;

use Mindy\Orm\Manager;

class QueueManager extends Manager
{
    public function incomplete()
    {
        return $this->filter(['is_complete' => false]);
    }
}

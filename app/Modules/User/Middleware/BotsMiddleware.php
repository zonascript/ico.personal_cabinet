<?php

namespace Modules\User\Middleware;

use Modules\User\Helpers\BotsHelper;
use Xcart\App\Middleware\Middleware;

class BotsMiddleware extends Middleware
{
    public function processRequest($request)
    {
        if (BotsHelper::IsBot()) {
            define("IS_ROBOT", 1);
            $GLOBALS['is_robot'] = 'Y';
        }
        else {
            $GLOBALS['is_robot'] = 'N';
        }
    }
}

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
 * @date 16/07/14.07.2014 13:34
 */

namespace Modules\User\Components;

use Mindy\Base\Mindy;
use Modules\Core\Models\UserLog;

/**
 * Class UserActionsTrait
 * @package Modules\User
 */
trait UserActionsTrait
{
    public static function recordAction($message, $module)
    {
        if (MINDY_TEST === false) {
            $app = Mindy::app();
            UserLog::objects()->create([
                'message' => $message,
                'module' => $module,
                'ip' => $app->getUser()->getIp(),
                'user' => $app->getUser()->getIsGuest() ? null : $app->getUser(),
            ]);
        }
    }
}
